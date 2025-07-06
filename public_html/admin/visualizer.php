<?php
// File: arg_game/admin/visualizer.php

require_once __DIR__ . '/../includes/session.php';
require_admin();
require_once __DIR__ . '/../includes/db.php';

$page_title = 'Game Logic Visualizer';
include __DIR__ . '/../templates/admin_header.php';

// Fetch all puzzles and prerequisites
$puzzles_result = $mysqli->query("SELECT id, title FROM puzzles ORDER BY display_order ASC");
$puzzles = $puzzles_result ? $puzzles_result->fetch_all(MYSQLI_ASSOC) : [];

$prereqs_result = $mysqli->query("SELECT puzzle_id, prerequisite_puzzle_id FROM puzzle_prerequisites");
$prereqs = $prereqs_result ? $prereqs_result->fetch_all(MYSQLI_ASSOC) : [];

// --- Generate Mermaid.js Graph Definition ---
$mermaid_definition = "graph TD\n";

// Define all nodes (puzzles)
foreach ($puzzles as $puzzle) {
    // Sanitize title for Mermaid syntax (remove quotes, etc.)
    $safe_title = htmlspecialchars($puzzle['title'], ENT_QUOTES, 'UTF-8');
    $mermaid_definition .= "    P{$puzzle['id']}[\"{$safe_title}\"]\n";
}

// Define all edges (prerequisites)
if (!empty($prereqs)) {
    foreach ($prereqs as $prereq) {
        $mermaid_definition .= "    P{$prereq['prerequisite_puzzle_id']} --> P{$prereq['puzzle_id']}\n";
    }
} else {
    // If no prereqs, just show the puzzles without connections
    if (count($puzzles) <= 1) {
        $mermaid_definition .= "    subgraph Game Flow\n";
        foreach ($puzzles as $puzzle) {
             $mermaid_definition .= "    P{$puzzle['id']}\n";
        }
        $mermaid_definition .= "    end\n";
    }
}


// Add styling definitions for the nodes
$mermaid_definition .= "\n    %% Node Styling\n";
foreach ($puzzles as $puzzle) {
     $mermaid_definition .= "    style P{$puzzle['id']} fill:#34495e,stroke:#8e44ad,stroke-width:2px,color:#fff\n";
}

// Make each node clickable, linking to the edit page in a new tab
$mermaid_definition .= "\n    %% Clickable Links\n";
foreach ($puzzles as $puzzle) {
    $edit_url = SITE_URL . '/admin/edit_puzzle.php?id=' . $puzzle['id'];
    // The '_blank' parameter tells Mermaid.js to open the link in a new window/tab.
    $mermaid_definition .= "    click P{$puzzle['id']} \"{$edit_url}\" \"Edit '" . htmlspecialchars($puzzle['title'], ENT_QUOTES) . "'\" _blank\n";
}
?>

<style>
    .mermaid {
        background: var(--admin-bg);
        border: 1px solid var(--admin-border);
        border-radius: 8px;
        padding: 1.5rem;
    }
    /* Style clickable nodes */
    .mermaid .clickable {
        cursor: pointer;
    }
</style>

<div class="container">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
        <h2>Game Logic Visualizer</h2>
    </div>
    <p>This flowchart shows the progression of puzzles based on the prerequisites you have set. Click on any puzzle to open its editor in a new tab.</p>

    <div class="mermaid">
        <?php echo $mermaid_definition; ?>
    </div>
</div>

<!-- Include Mermaid.js library -->
<script src="https://cdn.jsdelivr.net/npm/mermaid/dist/mermaid.min.js"></script>
<script>
    // Initialize Mermaid
    mermaid.initialize({ startOnLoad: true, theme: 'dark' });
</script>

<?php include __DIR__ . '/../templates/admin_footer.php'; ?>