<?php

include '../../db_connect.php';
include '../../customer/menu/menu_customer.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
if (!isset($_SESSION['manguoidung'])) {
    header("Location: ../../customer/user/login_customer.php");
    exit();
}

$manguoidung = $_SESSION['manguoidung'];

// Truy vấn thông tin giỏ hàng từ database
$sql = "SELECT giohang.*, sanpham.masanpham, sanpham.tensanpham, sanpham.giaban, sanpham.giamgia, sanpham.anh, sanpham.soluong AS sanpham_soluong
        FROM giohang
        INNER JOIN sanpham ON giohang.masanpham = sanpham.masanpham
        WHERE giohang.manguoidung = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $manguoidung);
$stmt->execute();
$result = $stmt->get_result();
$cartItems = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
}

// Truy vấn mã giảm giá
$sqlGiamGiaCongKhai = "SELECT * FROM giamgia WHERE loaigiamgia='Công khai'";
$resultGiamGiaCongKhai = $conn->query($sqlGiamGiaCongKhai);
$giamGiaCongKhai = [];

if ($resultGiamGiaCongKhai->num_rows > 0) {
    while ($row = $resultGiamGiaCongKhai->fetch_assoc()) {
        $giamGiaCongKhai[] = $row;
    }
}

// Truy vấn mã giảm giá riêng cho người dùng
$sqlGiamGiaRieng = "SELECT giamgia.* FROM giamgia
                    INNER JOIN magiamgianguoidung ON giamgia.magiamgia = magiamgianguoidung.magiamgia_id
                    WHERE giamgia.loaigiamgia = 'Riêng' AND magiamgianguoidung.manguoidung_id = ?";
$stmt = $conn->prepare($sqlGiamGiaRieng);
$stmt->bind_param("i", $manguoidung);
$stmt->execute();
$resultGiamGiaRieng = $stmt->get_result();
$giamGiaRieng = [];

if ($resultGiamGiaRieng->num_rows > 0) {
    while ($row = $resultGiamGiaRieng->fetch_assoc()) {
        $giamGiaRieng[] = $row;
    }
}

$allGiamGia = array_merge($giamGiaCongKhai, $giamGiaRieng);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="giohang.css?v=<?php echo time(); ?>">

    <title>Giỏ Hàng</title>
</head>

<body>
    <h1>Giỏ Hàng</h1>
    <div id="cart-container">

    </div>
    <div class="cart-summary">
        <p id="total-amount">Tổng tiền: 0 VNĐ</p>
        <button id="btn-show-discount">Xem mã giảm giá</button>
        <a href="#" id="btn-checkout" class="btn-checkout">Thanh Toán</a> <!-- Nút thanh toán -->
    </div>
    <div class="discount-popup" id="discountPopup">
        <div class="discount-popup-content">
            <span class="close-popup" id="closePopup">×</span>
            <h2>Mã Giảm Giá</h2>
            <ul id="discount-list">
                <?php foreach ($allGiamGia as $gg): ?>
                <li>
                    <span class="discount-code"><?= htmlspecialchars($gg['tenma']) ?></span>
                    <button class="btn-copy" data-code="<?= htmlspecialchars($gg['tenma']) ?>">Copy</button>
                    <p class="discount-description"><?= htmlspecialchars($gg['mota']) ?></p>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        let cartItems = <?php echo json_encode($cartItems); ?> || [];
        const cartContainer = document.getElementById('cart-container');
        const totalAmount = document.getElementById('total-amount');
        const discountPopup = document.getElementById('discountPopup');
        const closePopup = document.getElementById('closePopup');
        const showDiscountButton = document.getElementById('btn-show-discount');
        const checkoutButton = document.getElementById('btn-checkout'); // Lấy nút thanh toán

        function renderCart() {
            cartContainer.innerHTML = ''; // Clear previous content
            if (cartItems.length === 0) {
                cartContainer.innerHTML = '<p>Giỏ hàng của bạn đang trống.</p>';
                checkoutButton.style.display = 'none'; // Ẩn nút thanh toán khi giỏ hàng trống
                return;
            }
            checkoutButton.style.display = 'inline-block'; // Hiển thị nút thanh toán khi giỏ hàng không trống
            let totalPrice = 0;
            cartItems.forEach((item, index) => {
                let originalPrice = parseFloat(item.giaban);
                let discountedPrice = originalPrice;
                if (item.giamgia) {
                    discountedPrice = originalPrice - parseFloat(item.giamgia);
                }
                const cartItemDiv = document.createElement('div');
                cartItemDiv.classList.add('cart-item');
                let quantityControl = '';
                if (item.soluong > item.sanpham_soluong) {
                    quantityControl = `<p style="color:red;">Hết hàng</p>`;
                } else {
                    quantityControl = `<div class='quantity-control'>
                                    <button class='btn-change-quantity' data-index='${index}' data-action='minus' data-masanpham='${item.masanpham}'>-</button>
                                    <input type='number' value='${item.soluong}' min='1' class='item-quantity' data-index='${index}' readonly/>
                                    <button class='btn-change-quantity' data-index='${index}' data-action='plus' data-masanpham='${item.masanpham}' data-stock='${item.sanpham_soluong}'>+</button>
                                </div>`;
                }
                cartItemDiv.innerHTML = `
                      <div class="cart-item-image-container">
                         <img src='../../uploads/${item.anh.split(',')[0]}' alt='${item.tensanpham}' class='cart-item-image' />
                       </div>
                        <div class='cart-item-info'>
                            <h3>${item.tensanpham}</h3>
                             <p class="cart-item-price">
                                Giá: ${item.giamgia ? `<span class="discounted-price">${Number(discountedPrice).toLocaleString('vi-VN')} VNĐ</span>` : `${Number(originalPrice).toLocaleString('vi-VN')} VNĐ`}
                            </p>
                            ${quantityControl}
                            <button class='btn-remove-item' data-index='${index}'>Xóa</button>
                        </div>
                   `;
                totalPrice += discountedPrice * item.soluong
                cartContainer.appendChild(cartItemDiv);
            });
            totalAmount.innerText = `Tổng tiền: ${Number(totalPrice).toLocaleString('vi-VN')} VNĐ`;
        }
        renderCart();
        showDiscountButton.addEventListener('click', function() {
            discountPopup.style.display = 'flex';
        });
        closePopup.addEventListener('click', function() {
            discountPopup.style.display = 'none';
        });

        cartContainer.addEventListener('click', function(event) {
            if (event.target.classList.contains('btn-remove-item')) {
                const index = event.target.getAttribute('data-index');
                const masanpham = cartItems[index].masanpham;
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'update_cart.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        cartItems.splice(index, 1);
                        renderCart();
                        updateCartCounter();
                    } else {
                        console.error('Error updating cart:', xhr.status, xhr.statusText);
                    }
                };
                xhr.onerror = function() {
                    console.error('Error updating cart. Network error.');
                };
                xhr.send(`action=remove&masanpham=${masanpham}`);
            }
            if (event.target.classList.contains('btn-change-quantity')) {
                const index = parseInt(event.target.getAttribute('data-index'));
                const action = event.target.getAttribute('data-action');
                const masanpham = cartItems[index].masanpham;
                const stock = parseInt(event.target.getAttribute('data-stock'));
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'update_cart.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response.success) {
                                cartItems[index].soluong = parseInt(response.soluong);
                                renderCart();
                            } else {
                                alert(response.message);
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                    } else {
                        console.error('Error updating cart:', xhr.status, xhr.statusText);
                    }
                };
                xhr.onerror = function() {
                    console.error('Error updating cart. Network error.');
                };
                let newQuantity = cartItems[index].soluong;
                if (action === 'plus') {
                    if (cartItems[index].soluong < stock) {
                        newQuantity++;
                    } else {
                        alert('Số lượng sản phẩm trong kho không đủ!');
                        return;
                    }
                }
                if (action === 'minus') {
                    if (cartItems[index].soluong > 1) {
                        newQuantity--;
                    } else {
                        return;
                    }
                }
                xhr.send(`action=${action}&masanpham=${masanpham}&quantity=${newQuantity}`);
            }
        });

        function updateCartCounter() {
            let cartCount = cartItems.length;
            const cartCounterElement = document.getElementById('cart-counter');
            if (cartCounterElement) {
                cartCounterElement.innerText = cartCount;
            }
        }
        updateCartCounter();
        document.querySelectorAll('.btn-copy').forEach(button => {
            button.addEventListener('click', function() {
                const code = this.dataset.code;
                navigator.clipboard.writeText(code)
                    .then(() => {
                        alert('Đã sao chép mã: ' + code);
                    })
                    .catch(err => {
                        console.error('Không thể sao chép: ', err);
                    });
            });
        });
        // Xử lý sự kiện click cho nút thanh toán
        checkoutButton.addEventListener('click', function(event) {
            event.preventDefault(); // Ngăn chặn hành vi mặc định của thẻ <a>
            if (cartItems.length === 0) {
                alert('Giỏ hàng của bạn đang trống.');
                return;
            }
            // Chuyển đổi mảng cartItems thành chuỗi JSON
            const orderDetails = JSON.stringify(cartItems.map(item => {
                return {
                    id: item.masanpham,
                    name: item.tensanpham,
                    quantity: item.soluong,
                    price: item.giaban,
                    image: '../../uploads/' + item.anh.split(',')[
                    0], // Đường dẫn đầy đủ đến ảnh
                    discount: item.giamgia
                };
            }));
            // Mã hóa URL để truyền dữ liệu qua URL
            const encodedOrderDetails = encodeURIComponent(orderDetails);
            // Chuyển hướng đến trang thanh toán với dữ liệu giỏ hàng
            window.location.href = 'order_confirm.php?order_details=' + encodedOrderDetails;
        });
    });
    </script>
</body>

</html>
<?php
include '../../footer.php';?>