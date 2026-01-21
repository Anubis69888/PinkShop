async function addToCart(productId) {
    try {
        const response = await fetch('api/cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'add', productId: productId })
        });
        const result = await response.json();

        if (result.success) {
            Swal.fire({
                icon: 'success',
                title: 'เพิ่มลงตะกร้าแล้ว! 🛒',
                showConfirmButton: false,
                timer: 1500,
                toast: true,
                position: 'top-end',
                background: '#fff0f3',
                color: '#ff6b81'
            });

            // Optional: Update cart badge dynamically if implementing
            if (result.cartCount !== undefined) {
                updateCartBadge(result.cartCount);
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'ไม่สามารถเพิ่มสินค้าได้',
                text: result.message || 'กรุณาลองใหม่อีกครั้ง'
            });
        }
    } catch (e) {
        console.error(e);
        Swal.fire({
            icon: 'error',
            title: 'เกิดข้อผิดพลาด',
            text: 'ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต'
        });
    }
}

function updateCartBadge(count) {
    const badge = document.querySelector('.nav-badge');
    const cartBtn = document.querySelector('.nav-btn[title="ตะกร้าสินค้า"]');

    if (badge) {
        if (count > 0) {
            badge.innerText = count;
        } else {
            badge.remove();
        }
    } else if (count > 0 && cartBtn) {
        // Create badge if it doesn't exist
        const newBadge = document.createElement('span');
        newBadge.className = 'nav-badge';
        newBadge.innerText = count;
        cartBtn.appendChild(newBadge);
    }
}
