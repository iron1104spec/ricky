<?php

// Connect to the database
include 'config/db.php';

// Product image mapping
$product_images = [
    'Rice' => [
        'file' => 'bigas.jpg',
        'class' => 'img-rice'
    ],
    'Sugar' => [
        'file' => 'asukal.jpg',
        'class' => 'img-sugar'
    ],
    'Cooking Oil' => [
        'file' => 'mantika.jpg',
        'class' => 'img-oil'
    ],
    'Eggs' => [
        'file' => 'itlog.jpg',
        'class' => 'img-eggs'
    ],
    'Tissue' => [
        'file' => 'tisyu.jpg',
        'class' => 'img-tissue'
    ],
    'Canned Sardines' => [
        'file' => 'sardinas.jpg',
        'class' => 'img-sardines'
    ],
    'Instant Noodles' => [
        'file' => 'instantnodels.jpg',
        'class' => 'img-noodles'
    ],
    'Coffee' => [
        'file' => 'kape.jpg',
        'class' => 'img-coffee'
    ]
];

// Process customer order
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $customerName = trim($_POST['customer_name']);
    $contact = trim($_POST['contact']);
    $productId = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);

    // Validate customer information
    if (
        empty($customerName) ||
        empty($contact) ||
        $productId <= 0 ||
        $quantity <= 0
    ) {
        die("Invalid order information.");
    }

    // Start database transaction
    $conn->begin_transaction();

    try {

        // Check product availability
        $checkProduct = $conn->prepare(
            "SELECT stock FROM products WHERE product_id = ? FOR UPDATE"
        );

        $checkProduct->bind_param("i", $productId);
        $checkProduct->execute();

        $productResult = $checkProduct->get_result();

        if ($productResult->num_rows == 0) {
            throw new Exception("Product not found.");
        }

        $product = $productResult->fetch_assoc();

        if ($quantity > $product['stock']) {
            throw new Exception("Not enough stock available.");
        }

        // Insert customer information
        $customerQuery = $conn->prepare(
            "INSERT INTO customers (customer_name, contact)
             VALUES (?, ?)"
        );

        $customerQuery->bind_param("ss", $customerName, $contact);

        if (!$customerQuery->execute()) {
            throw new Exception("Failed to save customer information.");
        }

        $customerId = $conn->insert_id;

        // Insert order information
        $orderQuery = $conn->prepare(
            "INSERT INTO orders (customer_id, product_id, quantity)
             VALUES (?, ?, ?)"
        );

        $orderQuery->bind_param("iii", $customerId, $productId, $quantity);

        if (!$orderQuery->execute()) {
            throw new Exception("Failed to save order.");
        }

        // Deduct product stock
        $updateStock = $conn->prepare(
            "UPDATE products
             SET stock = stock - ?
             WHERE product_id = ?"
        );

        $updateStock->bind_param("ii", $quantity, $productId);

        if (!$updateStock->execute()) {
            throw new Exception("Failed to update product stock.");
        }

        // Save all database changes
        $conn->commit();

        echo "<script>alert('Order placed successfully!');</script>";

    } catch (Exception $e) {

        // Undo database changes if an error occurs
        $conn->rollback();

        die($e->getMessage());
    }
}

// Retrieve products from database
$sql = "SELECT * FROM products";
$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Community Supply Store</title>

    <link rel="stylesheet" href="assets/style.css">
    <script src="assets/script.js" defer></script>
</head>

<body>

    <!-- Website Header -->
    <header class="header">
        <h1>Community Supply Store</h1>

        <nav>
            <a href="#home">Home</a>
            <a href="#products">Products</a>
            <a href="#order">Order</a>
        </nav>
    </header>

    <!-- Homepage -->
    <section class="hero" id="home">

        <h2>Your Everyday Community Supplies</h2>

        <p>
            Find your daily essentials in one convenient place.
        </p>

        <a href="#products" class="shop-btn">
            Browse Products
        </a>

    </section>

    <!-- Product Listing -->
    <section class="products-section" id="products">

        <h2>Available Products</h2>

        <div class="product-container">

            <?php while ($row = $result->fetch_assoc()) {

                // Get product name
                $prod_name = $row['product_name'];

                // Get image filename and CSS class
                $img_file = $product_images[$prod_name]['file'] ?? 'placeholder.jpg';

                $img_class = $product_images[$prod_name]['class'] ?? 'img-default';
            ?>

                <div class="product-card">

                    <!-- Product Image -->
                    <img
                        src="assets/img/<?php echo htmlspecialchars($img_file); ?>"
                        alt="<?php echo htmlspecialchars($prod_name); ?>"
                        class="product-image <?php echo $img_class; ?>"
                    >

                    <!-- Product Information -->
                    <h3>
                        <?php echo htmlspecialchars($row['product_name']); ?>
                    </h3>

                    <p>
                        Price: ₱<?php echo number_format($row['price'], 2); ?>
                    </p>

                    <p>
                        Stock: <?php echo $row['stock']; ?>
                    </p>

                    <button
                        type="button"
                        onclick="selectProduct(<?php echo $row['product_id']; ?>)"
                    >
                        Order Now
                    </button>

                </div>

            <?php } ?>

        </div>

    </section>

    <!-- Customer Order Form -->
    <section class="order-section" id="order">

        <h2>Customer Order Form</h2>

        <form id="orderForm" method="POST">

            <label for="customer_name">Customer Name</label>

            <input
                type="text"
                id="customer_name"
                name="customer_name"
                required
            >

            <label for="contact">Contact Information</label>

            <input
                type="text"
                id="contact"
                name="contact"
                required
            >

            <label for="product">Select Product</label>

            <select id="product" name="product_id" required>

                <option value="">Choose a product</option>

                <?php
                $dropdownQuery = "SELECT * FROM products";
                $dropdownResult = $conn->query($dropdownQuery);

                while ($product = $dropdownResult->fetch_assoc()) {
                ?>

                    <option value="<?php echo $product['product_id']; ?>">
                        <?php echo htmlspecialchars($product['product_name']); ?>
                    </option>

                <?php } ?>

            </select>

            <label for="quantity">Quantity</label>

            <input
                type="number"
                id="quantity"
                name="quantity"
                min="1"
                required
            >

            <button type="submit">Place Order</button>

        </form>

    </section>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; 2026 Community Supply Store</p>
    </footer>

</body>
</html>
