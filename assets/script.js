
// Select a product from the product card
function selectProduct(productId) {
    const productSelect = document.getElementById('product');

    productSelect.value = productId;

    document.getElementById('order').scrollIntoView({
        behavior: 'smooth'
    });
}
