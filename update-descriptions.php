<?php
// Update product descriptions with detailed content

$conn = new mysqli('localhost', 'root', '', 'readers_haven');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$descriptions = [
    1 => "Start each day with intention and end it with gratitude. This beautifully designed journal helps you cultivate a positive mindset through daily gratitude practice. With guided prompts, inspirational quotes, and plenty of space for reflection, you'll discover how acknowledging life's blessings—big and small—can transform your perspective, reduce stress, and increase happiness. Perfect for anyone seeking more joy, peace, and mindfulness in their daily life. Features high-quality paper, a ribbon bookmark, and an elegant cover that makes it a joy to use every single day.",
    
    2 => "Deepen your spiritual journey with this thoughtfully crafted prayer journal. More than just a notebook, it's a sacred space to record your prayers, track answered prayers, and witness God's faithfulness in your life. With scripture prompts, guided reflection questions, and dedicated sections for praise, thanksgiving, and intercession, this journal helps you build a consistent, meaningful prayer practice. Watch your faith grow as you look back and see how your prayers have been answered. Whether you're new to prayer or deepening your practice, this journal will become your trusted companion in your spiritual walk.",
    
    3 => "Your complete roadmap to a healthier, stronger, more energized you! This comprehensive wellness journal combines fitness tracking, nutrition planning, and mindful living into one powerful tool. Track your workouts, plan balanced meals, monitor your progress, and celebrate every victory along the way. With expert tips on exercise, nutrition, sleep, and stress management, plus motivational content to keep you going when things get tough, you'll have everything you need to build sustainable healthy habits. Perfect for beginners starting their fitness journey or athletes looking to level up. Your transformation starts here!"
];

foreach ($descriptions as $id => $desc) {
    $stmt = $conn->prepare('UPDATE products SET description = ? WHERE id = ?');
    $stmt->bind_param('si', $desc, $id);
    
    if ($stmt->execute()) {
        echo "✅ Updated product $id - " . substr($desc, 0, 50) . "...\n";
    } else {
        echo "❌ Failed to update product $id\n";
    }
    $stmt->close();
}

echo "\n🎉 All product descriptions updated successfully!\n";

$conn->close();
?>
