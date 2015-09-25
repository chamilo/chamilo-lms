
<p>
Hello <?php echo $user->getUsername(); ?>
</p>
<?php
if (!isset($_GET['showqr'])) {
?>

<p>
<a href="?showqr=1">Show QR Code</a>
</p>

<?php
}
?>

<p>
<a href="?logout=1">Logout</a>
</p>
