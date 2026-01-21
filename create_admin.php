<?php
require_once 'includes/db.php';

$db = new DB();

// Check if admin already exists
$adminUser = $db->find('users', 'username', 'admin');

if ($adminUser) {
    echo "Admin user already exists!\n";
    echo "Username: " . $adminUser['username'] . "\n";
    echo "Password: (hidden)\n";
} else {
    // Create admin user
    $userId = $db->insert('users', [
        'username' => 'admin',
        'password' => password_hash('admin123', PASSWORD_DEFAULT),
        'fullname' => 'Administrator',
        'address' => 'Headquarters',
        'phone' => '0000000000',
        'is_admin' => true,
        'is_seller' => true,
        'avatar_config' => [
            'src' => 'https://api.dicebear.com/7.x/avataaars/svg?seed=admin'
        ]
    ]);

    echo "Admin user created successfully!\n";
    echo "Username: admin\n";
    echo "Password: admin123\n";
}
?>
