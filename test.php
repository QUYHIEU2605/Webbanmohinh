<?php
session_start(); // Start the session to store data
include '../db_connect.php';

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve data from the form
    $fullname = $_POST['fullname'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $city = $_POST['city'];
    $cartData = json_decode($_POST['cartData'], true);
    $paymentMethod = $_POST['payment_method'];
    $shippingCompany = $_POST['shipping_company'];
     $discountCode = isset($_POST['discount_code']) ? $_POST['discount_code'] : '';// Get discount code
    
    // Get customer information from the database
    $sql_customer = "INSERT INTO khachhang (tenkhachhang, diachi, sdt) VALUES (?, ?, ?)";
    $stmt_customer = $conn->prepare($sql_customer);
    if (!$stmt_customer) {
        die("Error preparing customer statement: " . $conn->error);
    }
    $stmt_customer->bind_param("sss", $fullname, $address, $phone);
    if (!$stmt_customer->execute()) {
        die("Error executing customer statement: " . $stmt_customer->error);
    }
    $customerId = $stmt_customer->insert_id;
    $stmt_customer->close();

    $totalPrice = 0;
    foreach ($cartData as $item) {
        $totalPrice += floatval($item['price']);
    }

   // Apply discount
    $discountAmount = 0;
    if (!empty($discountCode)) {
        $sql_discount = "SELECT giamtheotien, giamtheophantram FROM giamgia WHERE tenma = ?";
        $stmt_discount = $conn->prepare($sql_discount);
         if ($stmt_discount) {
             $stmt_discount->bind_param("s", $discountCode);
             $stmt_discount->execute();
            $result_discount = $stmt_discount->get_result();
             if ($result_discount->num_rows > 0) {
                $row_discount = $result_discount->fetch_assoc();
                   if ($row_discount['giamtheophantram'] !== null) {
                      $discountAmount = $totalPrice * floatval($row_discount['giamtheophantram']);
                   } elseif ($row_discount['giamtheotien'] !== null) {
                       $discountAmount = floatval($row_discount['giamtheotien']);
                   }
             }
             $stmt_discount->close();
        }
       
    }

    $totalPriceAfterDiscount =  $totalPrice - $discountAmount;

   // Get shipping company information from database
    $shippingCompanyPrice = 0;
     if(!empty($shippingCompany)){
        $sql_shipping = "SELECT giavanchuyen FROM donvivanchuyen WHERE madonvi = ?";
        $stmt_shipping = $conn->prepare($sql_shipping);
         if ($stmt_shipping) {
              $stmt_shipping->bind_param("i", $shippingCompany);
            $stmt_shipping->execute();
            $result_shipping = $stmt_shipping->get_result();
             if ($result_shipping->num_rows > 0) {
                $row_shipping = $result_shipping->fetch_assoc();
                  $shippingCompanyPrice = floatval($row_shipping['giavanchuyen']);

             }
              $stmt_shipping->close();
         }
    }
    
    $finalTotal = $totalPriceAfterDiscount + $shippingCompanyPrice;

    // Insert order details into chitietdonhang table
    $sql_order = "INSERT INTO chitietdonhang (masanpham, soluong, makhachhang, ngaydathang, phuongthucthanhtoan, madonvi, tongtien, trangthai) VALUES (?, ?, ?, NOW(), ?, ?, ?, ?)";
    $stmt_order = $conn->prepare($sql_order);
    if (!$stmt_order) {
        die("Error preparing order statement: " . $conn->error);
    }
    $orderStatus = 'Chờ xác nhận';
    $orderQuantity = 1;
    foreach ($cartData as $item) {
        $item_id = $item['id'];
        $stmt_order->bind_param("iiisids", $item_id, $orderQuantity, $customerId, $paymentMethod, $shippingCompany, $finalTotal, $orderStatus);
        if (!$stmt_order->execute()) {
            die("Error executing order statement: " . $stmt_order->error);
        }
    }
    $orderDetailId = $stmt_order->insert_id;
    $stmt_order->close();

    // Store data in the session for use in chitietdathang.php
    $_SESSION['order_details'] = [
        'fullname' => $fullname,
        'phone' => $phone,
        'address' => $address,
        'city' => $city,
        'cartData' => $cartData,
        'paymentMethod' => $paymentMethod,
        'shippingCompany' => $shippingCompany,
        'orderDetailId' => $orderDetailId,
         'totalPrice' => $totalPrice,
        'totalPriceAfterDiscount' => $totalPriceAfterDiscount,
        'shippingCompanyPrice' => $shippingCompanyPrice,
        'finalTotal' => $finalTotal,
         'discountCode' => $discountCode // Store the discount code
    ];


    // Redirect to the detail page
    header("Location: chitietdathang.php");
    exit();
}
$conn->close();
?>