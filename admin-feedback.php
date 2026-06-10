<?php
// Start session and include necessary files
require_once __DIR__ . '/header.php'; // Assuming this handles session and authentication

// Check if the user is an admin
if (! isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /login'); // Redirect to login if not admin
    exit();
}

// Include the Feedback class
require_once __DIR__ . '/Feedback.php';

// Create a Feedback model instance
$feedbackModel = new Feedback();

// Mark all feedback as read when the admin views the list
$feedbackModel->markAllAsRead();

// Fetch all feedback messages
$feedbacks = $feedbackModel->getAllFeedback();

// Page title
$page_title = 'Admin View Feedback';
?>

<div class="container">
    <div class="page-header">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <a href="/dashboard" class="button-back">&larr; Back to Dashboard</a>
    </div>

    <?php if (isset($_SESSION['action_success'])): ?>
        <div class="success" style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 0.25rem;"><?php echo $_SESSION['action_success']; unset($_SESSION['action_success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['action_error'])): ?>
        <div class="error" style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 0.25rem;"><?php echo $_SESSION['action_error']; unset($_SESSION['action_error']); ?></div>
    <?php endif; ?>

    <?php if (empty($feedbacks)) { ?>
        <p>No feedback available.</p>
    <?php } else { ?>
        <div class="feedback-list">
            <?php foreach ($feedbacks as $feedback) { ?>
                <?php 
                    $can_reply = !empty($feedback['user_id']);
                    $click_action = $can_reply 
                        ? "openSmsModal('{$feedback['user_id']}', '".htmlspecialchars($feedback['username'])."')" 
                        : "alert('This feedback was submitted anonymously.')";
                ?>
                <div class="message-card received" 
                     onclick="<?php echo $click_action; ?>"
                     style="cursor: pointer; border-left-color: <?php echo $can_reply ? '#007bff' : '#6c757d'; ?>;"
                     title="<?php echo $can_reply ? 'Click to Reply via SMS' : 'Anonymous Feedback'; ?>">
                    <div class="template-header">
                        <div class="party-info">
                            <div class="user-avatar" style="background-color: <?php echo $can_reply ? '#007bff' : '#6c757d'; ?>; width: 30px; height: 30px; font-size: 0.8rem; margin-right: 10px;">
                                <?php echo strtoupper(substr($feedback['username'] ?: 'A', 0, 1)); ?>
                            </div>
                            <span>
                                <strong><?php echo htmlspecialchars($feedback['username'] ?: 'Anonymous User'); ?></strong>
                                <br><small style="color:#888;"><?php echo htmlspecialchars($feedback['subject']); ?></small>
                            </span>
                        </div>
                        <span class="message-time"><?php echo (new DateTime($feedback['created_at']))->format('M j, g:i a'); ?></span>
                    </div>
                    <div class="template-body">
                        <p><?php  echo nl2br(htmlspecialchars($feedback['message'])); ?></p>
                    </div>
                    <div class="template-footer">
                        <span class="role-tag <?php echo $can_reply ? 'received' : ''; ?>" style="background: <?php echo $can_reply ? '#e7f3ff' : '#eee'; ?>; color: <?php echo $can_reply ? '#007bff' : '#666'; ?>;">
                            <?php echo $can_reply ? 'Registered User' : 'Anonymous'; ?>
                        </span>
                        <div class="message-actions">
                            <?php if ($can_reply): ?>
                                <button class="action-button" style="background-color: #007bff;">Reply SMS</button>
                            <?php endif; ?>
                            <a href="/admin-feedback/delete?id=<?php echo $feedback['id']; ?>" class="action-button button-delete" onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this feedback?');" style="background-color: #dc3545; color: white; padding: 6px 12px; text-decoration: none; border-radius: 6px; font-size: 0.8rem;">Delete</a>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    <?php } ?>
</div>

<style>
    .feedback-list { margin-top: 1.5rem; }
    .message-card { border: 1px solid #eef; padding: 0; margin-bottom: 1.2rem; border-radius: 12px; background: white; overflow: hidden; transition: all 0.3s ease; }
    .message-card.received { border-left: 5px solid #6c757d; }
    
    .message-card:hover { 
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        border-color: var(--primary-color);
    }

    .template-header { padding: 12px 15px; background: #fcfcff; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f5; }
    .party-info { display: flex; align-items: center; }
    .role-tag { text-transform: uppercase; font-size: 0.65rem; padding: 2px 6px; border-radius: 4px; font-weight: bold; }
    .template-body { padding: 15px; font-size: 0.95rem; color: #444; min-height: 50px; }
    .template-footer { padding: 10px 15px; background: #fafaff; display: flex; justify-content: space-between; align-items: center; }
    .message-time { font-size: 0.8rem; color: #bbb; }
    .message-actions { display: flex; gap: 8px; }
    .action-button { margin-top: 0; padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; border: none; color: white; }

    .main-footer a[href="/feedback"] { display: none; }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>