
<?php

// Connect to the database
include 'config/db.php';

// Delete an order
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_order'])) {

    $orderId = intval($_POST['order_id']);

    $deleteQuery = $conn->prepare(
        "DELETE FROM orders WHERE order_id = ?"
    );

    $deleteQuery->bind_param("i", $orderId);

    if ($deleteQuery->execute()) {
        echo "<script>alert('Order deleted successfully!');</script>";
    } else {
        echo "<script>alert('Failed to delete order.');</script>";
    }

    $deleteQuery->close();
}

// Retrieve order information
$sql = "SELECT
            orders.order_id,
            customers.customer_name,
            customers.contact,
            products.product_name,
            orders.quantity,
            orders.order_date
        FROM orders
        INNER JOIN customers
        ON orders.customer_id = customers.customer_id
        INNER JOIN products
        ON orders.product_id = products.product_id
        ORDER BY orders.order_id DESC";

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

    <title>Admin - Order List</title>

    <!-- Connect CSS -->
    <link rel="stylesheet" href="assets/style.css">
</head>

<body>

    <div class="admin-container">

        <h1>Customer Orders</h1>

        <a href="index.php" class="back-link">
            ← Back to Store
        </a>

        <div class="table-wrapper">

            <table class="orders-table">

                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Contact</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Order Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    <?php while ($row = $result->fetch_assoc()) { ?>

                        <tr>

                            <td>
                                <?php echo $row['order_id']; ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['customer_name']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['contact']); ?>
                            </td>

                            <td>
                                <?php echo htmlspecialchars($row['product_name']); ?>
                            </td>

                            <td>
                                <?php echo $row['quantity']; ?>
                            </td>

                            <td>
                                <?php echo $row['order_date']; ?>
                            </td>

                            <td>

                                <form method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this order?');">

                                    <input
                                        type="hidden"
                                        name="order_id"
                                        value="<?php echo $row['order_id']; ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="delete_order"
                                        class="delete-btn"
                                    >
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php } ?>

                </tbody>

            </table>

        </div>

    </div>

</body>
</html>
