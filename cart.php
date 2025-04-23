<?php
session_start();
require_once 'db_connection.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: login.php');
    exit();
}

$studentID = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle Add to Cart
if (isset($_POST['add_to_cart'])) {
    $tutorID = $_POST['tutor_id'];
    $hours = isset($_POST['hours']) ? intval($_POST['hours']) : 1;
    
    if ($hours < 1) {
        $error = "Hours must be at least 1";
    } else {
        // Check if tutor already in cart
        $stmt = $conn->prepare("SELECT cartID FROM student_cart WHERE studentID = ? AND tutorID = ? AND is_deleted = 0");
        $stmt->bind_param("ii", $studentID, $tutorID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing cart item
            $row = $result->fetch_assoc();
            $cartID = $row['cartID'];
            $stmt = $conn->prepare("UPDATE student_cart SET hours = ? WHERE cartID = ?");
            $stmt->bind_param("ii", $hours, $cartID);
            $stmt->execute();
            $message = "Cart updated successfully!";
        } else {
            // Add new cart item
            $stmt = $conn->prepare("INSERT INTO student_cart (studentID, tutorID, hours) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $studentID, $tutorID, $hours);
            $stmt->execute();
            $message = "Tutor added to cart!";
        }
    }
}

// Handle Remove from Cart
if (isset($_GET['remove'])) {
    $cartID = $_GET['remove'];
    
    // Verify this cart item belongs to the current student
    $stmt = $conn->prepare("SELECT studentID FROM student_cart WHERE cartID = ?");
    $stmt->bind_param("i", $cartID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['studentID'] == $studentID) {
            // Soft delete the cart item
            $stmt = $conn->prepare("UPDATE student_cart SET is_deleted = 1 WHERE cartID = ?");
            $stmt->bind_param("i", $cartID);
            $stmt->execute();
            $message = "Item removed from cart!";
        } else {
            $error = "You don't have permission to remove this item.";
        }
    }
}

// Handle Update Cart
if (isset($_POST['update_cart'])) {
    foreach ($_POST['hours'] as $cartID => $hours) {
        $hours = intval($hours);
        if ($hours < 1) {
            $error = "Hours must be at least 1";
            continue;
        }
        
        // Verify this cart item belongs to the current student
        $stmt = $conn->prepare("SELECT studentID FROM student_cart WHERE cartID = ?");
        $stmt->bind_param("i", $cartID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['studentID'] == $studentID) {
                $stmt = $conn->prepare("UPDATE student_cart SET hours = ? WHERE cartID = ?");
                $stmt->bind_param("ii", $hours, $cartID);
                $stmt->execute();
                $message = "Cart updated successfully!";
            }
        }
    }
}

// Fetch cart items
$stmt = $conn->prepare("
    SELECT sc.cartID, sc.tutorID, sc.hours, u.first_name, u.last_name, tp.hourly_rate
    FROM student_cart sc
    JOIN user u ON sc.tutorID = u.user_id
    JOIN tutorprofile tp ON u.user_id = tp.user_id
    WHERE sc.studentID = ? AND sc.is_deleted = 0
");
$stmt->bind_param("i", $studentID);
$stmt->execute();
$cartItems = $stmt->get_result();

// Calculate total
$total = 0;
$cartItemsArray = [];
while ($item = $cartItems->fetch_assoc()) {
    $item['subtotal'] = $item['hours'] * $item['hourly_rate'];
    $total += $item['subtotal'];
    $cartItemsArray[] = $item;
}

// Handle Checkout
if (isset($_POST['checkout']) && !empty($cartItemsArray)) {
    // Redirect to checkout page
    $_SESSION['cart_items'] = $cartItemsArray;
    $_SESSION['cart_total'] = $total;
    header('Location: checkout.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Peer Tutoring Platform</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    
    <?php include 'header.php'; ?>
    
    <div class="container my-5">
        <h1 class="mb-4">Your Tutoring Cart</h1>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (empty($cartItemsArray)): ?>
            <div class="alert alert-info">Your cart is empty. <a href="tutors.php">Browse tutors</a> to add to your cart.</div>
        <?php else: ?>
            <form method="post" action="">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Tutor</th>
                                <th>Hourly Rate</th>
                                <th>Hours</th>
                                <th>Subtotal</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItemsArray as $item): ?>
                                <tr>
                                    <td>
                                        <a href="tutor_profile.php?id=<?php echo $item['tutorID']; ?>">
                                            <?php echo htmlspecialchars($item['first_name'] . ' ' . $item['last_name']); ?>
                                        </a>
                                    </td>
                                    <td>$<?php echo number_format($item['hourly_rate'], 2); ?></td>
                                    <td>
                                        <input type="number" name="hours[<?php echo $item['cartID']; ?>]" 
                                               value="<?php echo $item['hours']; ?>" min="1" class="form-control" 
                                               style="width: 80px;">
                                    </td>
                                    <td>$<?php echo number_format($item['subtotal'], 2); ?></td>
                                    <td>
                                        <a href="cart.php?remove=<?php echo $item['cartID']; ?>" 
                                           class="btn btn-sm btn-danger" 
                                           onclick="return confirm('Are you sure you want to remove this item?')">
                                            Remove
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th>$<?php echo number_format($total, 2); ?></th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" name="update_cart" class="btn btn-secondary">Update Cart</button>
                    <button type="submit" name="checkout" class="btn btn-primary">Proceed to Checkout</button>
                </div>
            </form>
        <?php endif; ?>
        
        <div class="mt-4">
            <a href="tutors.php" class="btn btn-outline-primary">Continue Browsing Tutors</a>
        </div>
    </div>
    
    <div class="footer-bottom">
                <p>&copy; 2025 PeerLearn. All rights reserved.</p>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
