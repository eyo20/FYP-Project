<?php
session_start();
include("db_connection.php");

if (!isset($_SESSION['id'])) {
    echo "<p>Please log in to view your tutoring cart.</p>";
    echo "<a href='Login.php'>Login</a>";
    exit();
}

$student_id = $_SESSION['id'];
$total_price = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Your Tutoring Cart</title>
    <style>
        .tutor-box { border: 1px solid #ccc; padding: 15px; margin: 10px 0; display: flex; gap: 20px; }
        .tutor-box img { width: 150px; height: auto; }
        .cart-total { font-size: 22px; font-weight: bold; margin-top: 20px; }
        .checkout-btn { padding: 10px 30px; background: #0071e3; color: white; font-size: 18px; border: none; border-radius: 5px; }
    </style>
</head>
<body>

<h1>Your Tutoring Cart</h1>

<?php
$query = "SELECT sc.cartID, sc.hours, t.tutorName, t.subject, t.hourlyRate, t.tutorDescription, t.tutorImage
          FROM student_cart sc
          JOIN tutors t ON sc.tutorID = t.tutorID
          WHERE sc.studentID = '$student_id' AND sc.is_deleted = 0 AND t.is_deleted = 0";

$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) === 0) {
    echo "<p>Your tutoring cart is empty.</p>";
    echo "<a href='tutor_list.php'>Browse Tutors</a>";
    exit();
}

while ($row = mysqli_fetch_assoc($result)) {
    $subtotal = $row['hourlyRate'] * $row['hours'];
    $total_price += $subtotal;
    ?>
    <div class="tutor-box">
        <img src="images/<?php echo $row['tutorImage']; ?>" alt="Tutor Image">
        <div>
            <h2><?php echo $row['tutorName']; ?> - <?php echo $row['subject']; ?></h2>
            <p><?php echo $row['tutorDescription']; ?></p>
            <p>Hourly Rate: RM <?php echo number_format($row['hourlyRate'], 2); ?></p>
            <p>Hours Booked: <?php echo $row['hours']; ?></p>
            <p>Subtotal: RM <?php echo number_format($subtotal, 2); ?></p>
            <form method="post" action="remove_cart_item.php">
                <input type="hidden" name="cartID" value="<?php echo $row['cartID']; ?>">
                <button type="submit">Remove</button>
            </form>
        </div>
    </div>
<?php } ?>

<div class="cart-total">
    Total: RM <?php echo number_format($total_price, 2); ?>
</div>

<form action="checkout.php" method="post">
    <input type="hidden" name="totalPrice" value="<?php echo $total_price; ?>">
    <button class="checkout-btn" type="submit">Proceed to Checkout</button>
</form>

</body>
</html>
