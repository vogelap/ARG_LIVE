<?php
// Initialize a variable to hold the hashed password.
$hashed_password = '';

// Check if the form has been submitted using the POST method.
// The 'isset' function checks if the 'password' key exists in the $_POST superglobal array.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    // Retrieve the password from the form submission.
    $password_to_hash = $_POST['password'];

    // Use the password_hash() function to create a secure hash of the password.
    // PASSWORD_DEFAULT is the recommended algorithm, as it will be updated automatically
    // when new and stronger algorithms are supported by PHP.
    $hashed_password = password_hash($password_to_hash, PASSWORD_DEFAULT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hasher</title>
    <!-- Load Tailwind CSS for styling -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Use the Inter font family */
        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg max-w-md w-full">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Password Hasher</h1>
        
        <!-- The form submits the password to the same PHP file. -->
        <form action="hasher.php" method="POST" class="space-y-6">
            <div>
                <label for="password" class="text-sm font-medium text-gray-700">Enter Password:</label>
                <input type="password" name="password" id="password" required class="mt-1 block w-full px-3 py-2 bg-white border border-gray-300 rounded-md shadow-sm placeholder-gray-400 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
            </div>
            <div>
                <button type="submit" class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Hash Password
                </button>
            </div>
        </form>

        <?php
        // Only display the results section if a hash has been generated.
        if (!empty($hashed_password)) :
        ?>
            <div class="mt-8 p-4 bg-gray-50 rounded-lg border border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Hashed Password:</h2>
                <!-- The 'word-wrap' style ensures the long hash string doesn't break the layout. -->
                <p class="mt-2 text-sm text-gray-600" style="word-wrap: break-word;"><?php echo htmlspecialchars($hashed_password); ?></p>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
