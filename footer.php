<style>
footer {
    padding-top: 30px;
    background-color: #fefefe;
    padding: 40px 5%;
    font-family: 'Segoe UI', sans-serif;
    border-top: 4px solid #f4b400;
    color: #333;
    margin-top: auto;
    /* Pushes the footer to the bottom */
}

.footer-section {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 30px;
}

.footer-column {
    flex: 1 1 22%;
    min-width: 200px;
}

.footer-column h3 {
    font-size: 1.2em;
    margin-bottom: 15px;
    color: #f4b400;
    border-bottom: 2px solid #f4b400;
    padding-bottom: 5px;
}

.footer-column ul {
    list-style-type: none;
    padding: 0;
}

.footer-column ul li {
    margin-bottom: 10px;
}

.footer-column ul li a {
    text-decoration: none;
    color: #555;
    transition: color 0.3s ease;
}

.footer-column ul li a:hover {
    color: #f4b400;
}

/* Footer bottom section */
.footer2 {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    gap: 20px;
    font-size: 0.95em;
    border-top: 1px solid #eee;
    padding-top: 20px;
}

.footer-contact,
.footer-legal {
    flex: 1 1 45%;
    min-width: 280px;
}

.footer-contact h4 {
    margin-bottom: 10px;
    color: #333;
}

.footer-contact p,
.footer-legal p {
    margin: 6px 0;
    color: #444;
}

/* Social icons (nếu dùng) */
.footer-social a {
    margin-right: 10px;
}

.footer-social img {
    width: 24px;
    height: 24px;
}

/* Responsive */
@media (max-width: 768px) {
    .footer-section {
        flex-direction: column;
    }

    .footer2 {
        flex-direction: column;
    }

    .footer-column,
    .footer-contact,
    .footer-legal {
        flex: 1 1 100%;
    }

    footer {
        padding: 30px 20px;
    }
}
</style>
<footer>
    <div class="footer-section">
        <div class="footer-column">
            <h3>Giới thiệu về Figure</h3>
            <ul>
                <li><a href="#">Giới thiệu</a></li>
                <li><a href="#">Liên hệ hợp tác</a></li>
                <li><a href="#">Tin tức</a></li>
                <li><a href="#">Tin tuyển dụng</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Hỗ trợ khách hàng</h3>
            <ul>
                <li><a href="#">Tra cứu đơn hàng</a></li>
                <li><a href="#">Hướng dẫn mua hàng trực tuyến</a></li>
                <li><a href="#">Hướng dẫn thanh toán</a></li>
                <li><a href="#">Bảng tính giá Order</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Chính sách</h3>
            <ul>
                <li><a href="#">Quy định chung</a></li>
                <li><a href="#">Phân định trách nhiệm</a></li>
                <li><a href="#">Chính sách vận chuyển</a></li>
                <li><a href="#">Chính sách bảo mật</a></li>
                <li><a href="#">Chính sách đổi trả</a></li>
            </ul>
        </div>
        <div class="footer-column">
            <h3>Thông tin khuyến mãi</h3>
            <ul>
                <li><a href="#">Thông tin khuyến mãi</a></li>
                <li><a href="#">Sản phẩm khuyến mãi</a></li>
                <li><a href="#">Sản phẩm mới</a></li>
            </ul>
        </div>
    </div>
    <div class="footer2">
        <div class="footer-contact">
            <h4>Thông tin liên hệ</h4>
            <p><strong>Cơ sở 1:</strong> </p>
            <p><strong>Cơ sở 2:</strong> </p>
            <p>Email:viduvidu.vn@gmail.com</p>
            <p>Hotline 1:</p>
            <p>Hotline 2: </p>
        </div>

        <div class="footer-legal">
            <p>Người đại diện: </p>
            <p>MST: </p>
            <p>Ngày cấp: </p>
            <p>Nơi cấp: </p>
        </div>
    </div>
</footer>