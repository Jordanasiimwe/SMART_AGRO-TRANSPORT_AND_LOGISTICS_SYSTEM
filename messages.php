<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/Message.php';
require_once __DIR__ . '/User.php';

$messageModel = new Message();
$current_user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

$pageTitle = "My Messages";

// Mark messages as read since the user is viewing their inbox/system messages list
$messageModel->markAllAsReadForUser($current_user_id);

if ($user_role === 'admin') {
    $messages = $messageModel->getAllMessages();
    $pageTitle = "All System Messages";
} else {
    $messages = $messageModel->getMessagesForUser($current_user_id);
}

$userModel = new User();
$contactableUsers = $userModel->getContactableUsers($current_user_id);

?>

<div class="container">
    <div class="page-header">
        <h1><?php echo $pageTitle; ?></h1>
        <div class="page-header-actions">
            <button class="action-button button-compose" onclick="openComposeModal()">Compose New Message</button>
            <a href="/dashboard" class="button-back">&larr; Back to Dashboard</a>
        </div>
    </div>

    <?php if (isset($_SESSION['action_success'])): ?>
        <div class="success" style="color: #155724; background-color: #d4edda; border: 1px solid #c3e6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 0.25rem;"><?php echo $_SESSION['action_success']; unset($_SESSION['action_success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['action_error'])): ?>
        <div class="error" style="color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; padding: 0.75rem 1.25rem; margin-bottom: 1rem; border-radius: 0.25rem;"><?php echo $_SESSION['action_error']; unset($_SESSION['action_error']); ?></div>
    <?php endif; ?>

    <div class="message-list">
        <?php if (empty($messages)): ?>
            <p>You have no messages.</p>
        <?php else: ?>
            <?php foreach ($messages as $msg): ?>
                <?php
                    $is_sent = ($msg['sender_id'] == $current_user_id);
                    $card_class = $is_sent ? 'sent' : 'received';
                    
                    if ($user_role === 'admin' && !$is_sent) {
                        $card_class = 'received'; // Admins see everything from others as "received"
                    }

                    $other_party_id = $is_sent ? $msg['recipient_id'] : $msg['sender_id'];
                    $other_party_name = $is_sent ? $msg['recipient_name'] : $msg['sender_name'];
                ?>
                <div class="message-card <?php echo $card_class; ?>" 
                     onclick="openSmsModal('<?php echo $other_party_id; ?>', '<?php echo htmlspecialchars($other_party_name); ?>')"
                     title="Click to Reply"
                     style="cursor: pointer;">
                    <div class="template-header">
                        <div class="party-info">
                            <div class="user-avatar" style="width: 30px; height: 30px; font-size: 0.8rem; margin-right: 10px;">
                                <?php echo strtoupper(substr($other_party_name, 0, 1)); ?>
                            </div>
                            <span>
                                <?php if ($is_sent): ?>
                                    <small class="role-tag sent">To:</small> <strong><?php echo htmlspecialchars($msg['recipient_name']); ?></strong>
                                <?php else: ?>
                                    <small class="role-tag received">From:</small> <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong>
                                <?php endif; ?>
                            </span>
                        </div>
                        <span class="message-time"><?php echo (new DateTime($msg['created_at']))->format('M j, g:i a'); ?></span>
                    </div>
                    <div class="template-body">
                        <p><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
                    </div>
                    <div class="template-footer">
                        <div class="message-type-indicator">
                            <?php echo $is_sent ? '&#10148; Sent' : '&#10554; Received'; ?>
                        </div>
                        <div class="message-actions">
                            <button class="action-button button-sms">Reply</button>
                            <a href="/messages/delete?id=<?php echo $msg['id']; ?>" class="action-button button-delete" onclick="event.stopPropagation(); return confirm('Are you sure you want to delete this message?');">Delete</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .page-header-actions { display: flex; gap: 1rem; align-items: center; }
    .message-list { margin-top: 1.5rem; }
    .message-card { border: 1px solid #eef; padding: 0; margin-bottom: 1.2rem; border-radius: 12px; background: white; overflow: hidden; transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1); }
    .message-card.sent { border-left: 5px solid #007bff; }
    .message-card.received { border-left: 5px solid #28a745; }
    
    .message-card:hover { 
        transform: translateY(-4px);
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
    }

    .template-header { padding: 12px 15px; background: #fcfcff; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f0f0f5; }
    .party-info { display: flex; align-items: center; }
    .role-tag { text-transform: uppercase; font-size: 0.65rem; padding: 2px 5px; border-radius: 4px; margin-right: 5px; font-weight: bold; }
    .role-tag.sent { background: #e7f3ff; color: #007bff; }
    .role-tag.received { background: #eef9f1; color: #28a745; }

    .template-body { padding: 15px; font-size: 0.95rem; color: #444; min-height: 60px; }
    .template-footer { padding: 10px 15px; background: #fafaff; display: flex; justify-content: space-between; align-items: center; }
    
    .message-type-indicator { font-size: 0.8rem; color: #999; font-weight: 600; }
    .message-time { font-size: 0.8rem; color: #bbb; }

    .message-actions { display: flex; gap: 8px; }
    .action-button { margin-top: 0; padding: 6px 12px; font-size: 0.8rem; border-radius: 6px; }

    .message-actions .button-sms { width: auto; margin-top: 0; padding: 0.4rem 0.8rem; font-size: 0.85rem; }
    .button-compose {
        background-color: var(--primary-color);
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
    }
</style>

<!-- Compose Modal -->
<div id="composeModal" class="modal no-print">
    <div class="modal-content">
        <span class="close-btn" onclick="closeComposeModal()">&times;</span>
        <h3>Compose New Message</h3>
        <form id="composeForm" onsubmit="sendComposeSms(event)">
            <div class="form-group">
                <label for="composeRecipientId">To:</label>
                <select id="composeRecipientId" name="recipient_id" required>
                    <option value="">-- Select a recipient --</option>
                    <?php foreach ($contactableUsers as $user): ?>
                        <option value="<?php echo $user['id']; ?>">
                            <?php echo htmlspecialchars($user['username']) . ' (' . ucfirst($user['role_name']) . ')'; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="composeMessage">Message</label>
                <textarea id="composeMessage" name="message" rows="5" required maxlength="160"></textarea>
            </div>
            <button type="submit">Send Message</button>
            <div id="composeStatus" style="margin-top: 1rem;"></div>
        </form>
    </div>
</div>

<script>
    const composeModal = document.getElementById('composeModal');

    function openComposeModal() {
        composeModal.style.display = 'block';
    }

    function closeComposeModal() {
        composeModal.style.display = 'none';
        document.getElementById('composeStatus').innerHTML = '';
        document.getElementById('composeForm').reset();
    }

    // The existing sendSms function in footer.php is tied to the #smsForm.
    // We'll create a specific one here that uses the #composeForm and its status div.
    function sendComposeSms(e) {
        e.preventDefault();
        const statusDiv = document.getElementById('composeStatus');
        statusDiv.innerHTML = '<p class="info">Sending...</p>';
        const formData = new FormData(e.target);

        fetch('/api/send-sms', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            const messageClass = data.success ? 'success' : 'error';
            statusDiv.innerHTML = `<p class="${messageClass}">${data.message}</p>`;
            if (data.success) {
                setTimeout(() => {
                    closeComposeModal();
                    window.location.reload(); // Reload to see the new sent message
                }, 2000);
            }
        })
        .catch(error => {
            statusDiv.innerHTML = '<p class="error">An unexpected error occurred. Please check the browser console for details.</p>';
            console.error('Compose SMS Error:', error);
        });
    }

    // Also close modal if clicking outside of it
    window.addEventListener('click', function(event) {
        if (event.target == composeModal) {
            closeComposeModal();
        }
    });
</script>

<?php require_once __DIR__ . '/footer.php'; ?>