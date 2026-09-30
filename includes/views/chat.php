<?php
require dirname(__DIR__) . '/app.php';
require dirname(__DIR__) . '/layout.php';
require_once dirname(__DIR__) . '/chat-functions.php';
$user = require_roles([$chat_role]);
$contacts = chat_contacts($user);
$contact_id = (int)query_input('contact', (string)($contacts[0]['id'] ?? 0), 20);
$contact = null;
$messages = [];
if ($contact_id) {
    try {
        $contact = chat_contact($user, $contact_id);
        chat_mark_read($user['id'], $contact_id);
        $messages = rows('SELECT * FROM (SELECT id,sender_id,recipient_id,body,created_at FROM messages WHERE (sender_id=? AND recipient_id=?) OR (sender_id=? AND recipient_id=?) ORDER BY id DESC LIMIT 100) recent ORDER BY id', 'iiii', [$user['id'], $contact_id, $contact_id, $user['id']]);
    } catch (Exception $error) {
        $contact_id = 0;
    }
}
$paths = ['customer' => 'pages/customer/chat.php', 'staff' => 'pages/staff/chat.php', 'gardener' => 'pages/gardener/chat.php'];
$return = $paths[$chat_role] . '?contact=' . $contact_id;
$unread = chat_unread($user);
$contact_unread = [];
foreach ($unread['contacts'] as $item) $contact_unread[$item['sender_id']] = (int)$item['total'];
page_start('Live chat');
heading('Let’s talk plants', $chat_role === 'staff' ? 'Message your customers and gardeners.' : 'Message nursery staff for help with plants and garden visits.');
?><section class="panel chat-layout" id="liveChat" data-api="<?= h(url('api/index.php')) ?>" data-contact="<?= $contact_id ?>" data-user="<?= (int)$user['id'] ?>" data-csrf="<?= h($_SESSION['csrf']) ?>">
    <nav class="contact-list" aria-label="Chat contacts"><?php foreach ($contacts as $person): ?><a class="<?= $contact_id == $person['id'] ? 'active' : '' ?>" href="<?= h(url($paths[$chat_role] . '?contact=' . $person['id'])) ?>"><?= h($person['name']) ?> <span class="unread-badge" data-unread-contact="<?= (int)$person['id'] ?>" <?= empty($contact_unread[$person['id']]) ? 'hidden' : '' ?>><?= (int)($contact_unread[$person['id']] ?? 0) ?></span><br><small><?= h($person['role']) ?></small></a><?php endforeach; ?></nav>
    <div><?php if ($contact): ?><h2><?= h($contact['name']) ?></h2>
            <div class="chat-box" id="chatMessages" role="log" aria-live="polite" data-after="<?= (int)($messages ? end($messages)['id'] : 0) ?>"><?php foreach ($messages as $message): ?><div class="message <?= $message['sender_id'] == $user['id'] ? 'mine' : '' ?>" data-message-id="<?= (int)$message['id'] ?>"><?= h($message['body']) ?><time><?= h($message['created_at']) ?></time></div><?php endforeach; ?></div><?php post_form('message_send', $return, 'chat-compose'); ?><input type="hidden" name="contactId" value="<?= $contact_id ?>"><label style="flex:1">Message<input name="message" maxlength="2000" required autocomplete="off"></label><button class="btn">Send</button></form>
            <p id="chatStatus" class="muted space" role="status"></p>
        <?php else: ?><p class="empty">No contacts available. Ask the admin to add nursery staff, or choose a contact.</p><?php endif; ?>
    </div>
</section>
<script src="<?= h(url('assets/js/chat.js')) ?>" defer></script>
<?php page_end(); ?>