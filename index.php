<?php
/**
 * Entertainment Tadka Bot - COMPLETE ULTIMATE VERSION
 * Features: 4 Public + 2 Private Channels + Request Group + 11 Admin Features
 * Version: 5.0 FINAL
 */

// -------------------- ERROR REPORTING --------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ==============================
// .ENV LOADER FUNCTION
// ==============================
function loadEnv($filePath = '.env') {
    if (!file_exists($filePath)) return;
    $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            if (strpos($value, '"') === 0 || strpos($value, "'") === 0) $value = substr($value, 1, -1);
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

loadEnv(__DIR__ . '/.env');

// -------------------- CONFIG FROM .ENV --------------------
define('BOT_TOKEN', getenv('BOT_TOKEN') ?: '');
define('CHANNEL_1_ID', getenv('CHANNEL_1_ID') ?: '');
define('CHANNEL_1_USERNAME', getenv('CHANNEL_1_USERNAME') ?: '');
define('CHANNEL_2_ID', getenv('CHANNEL_2_ID') ?: '');
define('CHANNEL_2_USERNAME', getenv('CHANNEL_2_USERNAME') ?: '');
define('CHANNEL_3_ID', getenv('CHANNEL_3_ID') ?: '');
define('CHANNEL_3_USERNAME', getenv('CHANNEL_3_USERNAME') ?: '');
define('CHANNEL_4_ID', getenv('CHANNEL_4_ID') ?: '');
define('CHANNEL_4_USERNAME', getenv('CHANNEL_4_USERNAME') ?: '');
define('PRIVATE_CHANNEL_1_ID', getenv('PRIVATE_CHANNEL_1_ID') ?: '');
define('PRIVATE_CHANNEL_2_ID', getenv('PRIVATE_CHANNEL_2_ID') ?: '');
define('REQUEST_GROUP_ID', getenv('REQUEST_GROUP_ID') ?: '');
define('REQUEST_GROUP_USERNAME', getenv('REQUEST_GROUP_USERNAME') ?: '');
define('ADMIN_ID', getenv('ADMIN_ID') ?: '1080317415');
define('CSV_FILE', getenv('CSV_FILE') ?: 'movies.csv');
define('USERS_FILE', getenv('USERS_FILE') ?: 'users.json');
define('STATS_FILE', getenv('STATS_FILE') ?: 'bot_stats.json');
define('ITEMS_PER_PAGE', getenv('ITEMS_PER_PAGE') ?: 5);
define('DEFAULT_CHANNEL_ID', CHANNEL_1_ID);
define('DEFAULT_CHANNEL_USERNAME', CHANNEL_1_USERNAME);

// Extra files
define('BACKUP_DIR', 'backups/');
define('SCHEDULES_FILE', 'schedules.json');
define('COMMENTS_FILE', 'comments.json');
define('REACTIONS_FILE', 'reactions_settings.json');
define('NOTIFY_FILE', 'notify_settings.json');
define('SETTINGS_FILE', 'settings.json');
define('DISCUSSION_FILE', 'discussion_groups.json');

// All channel IDs array
$ALL_CHANNEL_IDS = [];
if (CHANNEL_1_ID) $ALL_CHANNEL_IDS[] = CHANNEL_1_ID;
if (CHANNEL_2_ID) $ALL_CHANNEL_IDS[] = CHANNEL_2_ID;
if (CHANNEL_3_ID) $ALL_CHANNEL_IDS[] = CHANNEL_3_ID;
if (CHANNEL_4_ID) $ALL_CHANNEL_IDS[] = CHANNEL_4_ID;
if (PRIVATE_CHANNEL_1_ID) $ALL_CHANNEL_IDS[] = PRIVATE_CHANNEL_1_ID;
if (PRIVATE_CHANNEL_2_ID) $ALL_CHANNEL_IDS[] = PRIVATE_CHANNEL_2_ID;

// All channels array for display
$ALL_CHANNELS = [];
if (CHANNEL_1_ID) $ALL_CHANNELS[] = ['id' => CHANNEL_1_ID, 'username' => CHANNEL_1_USERNAME, 'name' => 'Main Channel', 'type' => 'public'];
if (CHANNEL_2_ID) $ALL_CHANNELS[] = ['id' => CHANNEL_2_ID, 'username' => CHANNEL_2_USERNAME, 'name' => 'Serials Channel', 'type' => 'public'];
if (CHANNEL_3_ID) $ALL_CHANNELS[] = ['id' => CHANNEL_3_ID, 'username' => CHANNEL_3_USERNAME, 'name' => 'Theater Print', 'type' => 'public'];
if (CHANNEL_4_ID) $ALL_CHANNELS[] = ['id' => CHANNEL_4_ID, 'username' => CHANNEL_4_USERNAME, 'name' => 'Backup Channel', 'type' => 'public'];
if (PRIVATE_CHANNEL_1_ID) $ALL_CHANNELS[] = ['id' => PRIVATE_CHANNEL_1_ID, 'username' => null, 'name' => 'Private Channel 1', 'type' => 'private'];
if (PRIVATE_CHANNEL_2_ID) $ALL_CHANNELS[] = ['id' => PRIVATE_CHANNEL_2_ID, 'username' => null, 'name' => 'Private Channel 2', 'type' => 'private'];

// -------------------- FILE INITIALIZATION --------------------
if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, json_encode(['users' => [], 'pending_requests' => []]));
    @chmod(USERS_FILE, 0666);
}
if (!file_exists(CSV_FILE)) {
    file_put_contents(CSV_FILE, "movie_name,message_id,date,channel_id,channel_username,channel_type\n");
    @chmod(CSV_FILE, 0666);
}
if (!file_exists(STATS_FILE)) {
    file_put_contents(STATS_FILE, json_encode(['total_movies' => 0, 'total_users' => 0, 'total_searches' => 0, 'last_updated' => date('Y-m-d H:i:s')]));
    @chmod(STATS_FILE, 0666);
}
if (!file_exists(BACKUP_DIR)) @mkdir(BACKUP_DIR, 0777, true);
if (!file_exists(SCHEDULES_FILE)) file_put_contents(SCHEDULES_FILE, json_encode([]));
if (!file_exists(COMMENTS_FILE)) file_put_contents(COMMENTS_FILE, json_encode([]));
if (!file_exists(SETTINGS_FILE)) {
    $default_settings = ['bot' => ['maintenance_mode' => false, 'search_enabled' => true, 'request_enabled' => true, 'language' => 'english', 'items_per_page' => 5], 'filters' => ['min_search_length' => 2, 'block_technical_queries' => true], 'notifications' => ['daily_digest' => true, 'digest_time' => '08:00', 'new_comment_notify' => true], 'security' => ['blocked_users' => []]];
    file_put_contents(SETTINGS_FILE, json_encode($default_settings, JSON_PRETTY_PRINT));
}
if (!file_exists(DISCUSSION_FILE)) file_put_contents(DISCUSSION_FILE, json_encode([]));

// -------------------- NOTIFICATION SETUP --------------------
if (!file_exists(NOTIFY_FILE)) {
    $default_notify = [];
    foreach ($ALL_CHANNEL_IDS as $channel_id) $default_notify[$channel_id] = ['default_notification' => true, 'temp_silent' => false, 'temp_until' => null];
    file_put_contents(NOTIFY_FILE, json_encode($default_notify, JSON_PRETTY_PRINT));
}

// -------------------- REACTIONS SETUP --------------------
$AVAILABLE_REACTIONS = ['👍', '👎', '❤️', '🔥', '🥰', '😢', '😡', '😮', '🎉', '🤯'];
if (!file_exists(REACTIONS_FILE)) {
    $default_reactions = [];
    foreach ($ALL_CHANNEL_IDS as $channel_id) $default_reactions[$channel_id] = ['enabled' => false, 'reactions' => ['👍', '❤️', '🔥']];
    file_put_contents(REACTIONS_FILE, json_encode($default_reactions, JSON_PRETTY_PRINT));
}

// -------------------- GLOBAL CACHES --------------------
$movie_messages = [];
$movie_cache = [];
$waiting_users = [];
$create_post_step = [];
$schedule_step = [];
$edit_step = [];
$delete_step = [];
$buttons_step = [];
$comment_step = [];
$react_step = [];
$discuss_step = [];
$settings_step = [];
$notify_step = [];

// ==============================
// STATS FUNCTIONS
// ==============================
function update_stats($field, $increment = 1) {
    if (!file_exists(STATS_FILE)) return;
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $stats[$field] = ($stats[$field] ?? 0) + $increment;
    $stats['last_updated'] = date('Y-m-d H:i:s');
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
}
function get_stats() {
    if (!file_exists(STATS_FILE)) return [];
    return json_decode(file_get_contents(STATS_FILE), true);
}
function get_settings() {
    if (!file_exists(SETTINGS_FILE)) return [];
    return json_decode(file_get_contents(SETTINGS_FILE), true);
}
function save_settings($settings) {
    file_put_contents(SETTINGS_FILE, json_encode($settings, JSON_PRETTY_PRINT));
}

// ==============================
// CSV FUNCTIONS
// ==============================
function load_and_clean_csv() {
    global $movie_messages;
    if (!file_exists(CSV_FILE)) {
        file_put_contents(CSV_FILE, "movie_name,message_id,date,channel_id,channel_username,channel_type\n");
        return [];
    }
    $data = [];
    $handle = fopen(CSV_FILE, "r");
    if ($handle !== FALSE) {
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) >= 3 && (!empty(trim($row[0])))) {
                $entry = ['movie_name' => trim($row[0]), 'message_id_raw' => isset($row[1]) ? trim($row[1]) : '', 'date' => isset($row[2]) ? trim($row[2]) : '', 'channel_id' => isset($row[3]) ? trim($row[3]) : DEFAULT_CHANNEL_ID, 'channel_username' => isset($row[4]) ? trim($row[4]) : DEFAULT_CHANNEL_USERNAME, 'channel_type' => isset($row[5]) ? trim($row[5]) : 'public', 'message_id' => is_numeric(trim($row[1])) ? intval(trim($row[1])) : null];
                $data[] = $entry;
                $movie = strtolower($entry['movie_name']);
                if (!isset($movie_messages[$movie])) $movie_messages[$movie] = [];
                $movie_messages[$movie][] = $entry;
            }
        }
        fclose($handle);
    }
    $stats = json_decode(file_get_contents(STATS_FILE), true);
    $stats['total_movies'] = count($data);
    file_put_contents(STATS_FILE, json_encode($stats, JSON_PRETTY_PRINT));
    return $data;
}
function get_cached_movies() {
    global $movie_cache;
    $movie_cache = load_and_clean_csv();
    return $movie_cache;
}
function get_all_movies_list() { return get_cached_movies(); }
function remove_movie_from_csv($message_id, $channel_id) {
    if (!file_exists(CSV_FILE)) return;
    $rows = []; $deleted = false;
    $handle = fopen(CSV_FILE, "r");
    if ($handle !== FALSE) {
        $header = fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (!($row[1] == $message_id && $row[3] == $channel_id)) $rows[] = $row;
            else $deleted = true;
        }
        fclose($handle);
    }
    if ($deleted) {
        $handle = fopen(CSV_FILE, "w");
        fputcsv($handle, ['movie_name', 'message_id', 'date', 'channel_id', 'channel_username', 'channel_type']);
        foreach ($rows as $row) fputcsv($handle, $row);
        fclose($handle);
        global $movie_cache, $movie_messages;
        $movie_cache = []; $movie_messages = [];
        update_stats('total_movies', -1);
    }
}

// ==============================
// TELEGRAM API
// ==============================
function apiRequest($method, $params = [], $is_multipart = false) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    if ($is_multipart) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
    $options = ['http' => ['method' => 'POST', 'content' => http_build_query($params), 'header' => "Content-Type: application/x-www-form-urlencoded\r\n"]];
    $context = stream_context_create($options);
    return @file_get_contents($url, false, $context);
}
function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    $data = ['chat_id' => $chat_id, 'text' => $text];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    return apiRequest('sendMessage', $data);
}
function forwardMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('forwardMessage', ['chat_id' => $chat_id, 'from_chat_id' => $from_chat_id, 'message_id' => $message_id]);
}
function forwardMessageWithNotify($chat_id, $from_chat_id, $message_id, $disable_notification = false) {
    return apiRequest('forwardMessage', ['chat_id' => $chat_id, 'from_chat_id' => $from_chat_id, 'message_id' => $message_id, 'disable_notification' => $disable_notification]);
}
function answerCallbackQuery($callback_query_id, $text = null) {
    $data = ['callback_query_id' => $callback_query_id];
    if ($text) $data['text'] = $text;
    apiRequest('answerCallbackQuery', $data);
}
function editMessageText($chat_id, $message_id, $text, $reply_markup = null, $parse_mode = 'HTML') {
    $data = ['chat_id' => $chat_id, 'message_id' => $message_id, 'text' => $text, 'parse_mode' => $parse_mode];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    return apiRequest('editMessageText', $data);
}
function editMessageReplyMarkup($chat_id, $message_id, $reply_markup) {
    return apiRequest('editMessageReplyMarkup', ['chat_id' => $chat_id, 'message_id' => $message_id, 'reply_markup' => json_encode($reply_markup)]);
}

// ==============================
// NOTIFICATION FUNCTIONS
// ==============================
function get_notify_settings($channel_id = null) {
    $settings = json_decode(file_get_contents(NOTIFY_FILE), true);
    if ($channel_id) return isset($settings[$channel_id]) ? $settings[$channel_id] : ['default_notification' => true, 'temp_silent' => false, 'temp_until' => null];
    return $settings;
}
function save_notify_settings($channel_id, $settings) {
    $all_settings = get_notify_settings();
    $all_settings[$channel_id] = $settings;
    file_put_contents(NOTIFY_FILE, json_encode($all_settings, JSON_PRETTY_PRINT));
}
function set_channel_notification($channel_id, $enabled) {
    $settings = get_notify_settings($channel_id);
    $settings['default_notification'] = $enabled;
    save_notify_settings($channel_id, $settings);
}
function set_temp_silent($channel_id, $duration_minutes = 60) {
    $settings = get_notify_settings($channel_id);
    $settings['temp_silent'] = true;
    $settings['temp_until'] = date('Y-m-d H:i:s', strtotime("+$duration_minutes minutes"));
    save_notify_settings($channel_id, $settings);
}
function clear_temp_silent($channel_id) {
    $settings = get_notify_settings($channel_id);
    $settings['temp_silent'] = false;
    $settings['temp_until'] = null;
    save_notify_settings($channel_id, $settings);
}
function should_send_silent($channel_id) {
    $settings = get_notify_settings($channel_id);
    if ($settings['temp_silent'] && $settings['temp_until'] && strtotime($settings['temp_until']) > time()) return true;
    if ($settings['temp_silent'] && $settings['temp_until'] && strtotime($settings['temp_until']) <= time()) clear_temp_silent($channel_id);
    return !$settings['default_notification'];
}
function notify_settings_menu($chat_id, $user_id) {
    global $ALL_CHANNELS;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) {
        $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name'];
        $settings = get_notify_settings($ch['id']);
        $status = $settings['default_notification'] ? '🔔 ON' : '🔕 OFF';
        $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name - $status", 'callback_data' => 'notify_channel_' . $ch['id']]];
    }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Close', 'callback_data' => 'notify_close']];
    sendMessage($chat_id, "🔔 <b>Notification Settings</b>\n\nControl how posts are delivered:\n• 🔔 ON = Users get sound/vibration\n• 🔕 OFF = Silent post\n\nSelect a channel:", $keyboard, 'HTML');
}
function notify_channel_menu($chat_id, $user_id, $channel_id) {
    global $ALL_CHANNELS;
    $channel_name = $channel_id;
    foreach ($ALL_CHANNELS as $ch) if ($ch['id'] == $channel_id) $channel_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name'];
    $settings = get_notify_settings($channel_id);
    $msg = "🔔 <b>Notification Settings</b>\n\n📺 Channel: $channel_name\n🆔 ID: <code>$channel_id</code>\n\n━━━━━━━━━━━━━━━━━━━━\n📊 Current:\n• Default: " . ($settings['default_notification'] ? '🔔 NORMAL' : '🔕 SILENT') . "\n• Temp Silent: " . ($settings['temp_silent'] ? "Active until {$settings['temp_until']}" : 'Inactive') . "\n\n💡 Use cases:\n• Bulk uploads → Silent\n• Important updates → Normal\n• Night time → Temp Silent";
    $keyboard = ['inline_keyboard' => [
        [['text' => '🔔 Set NORMAL', 'callback_data' => 'notify_normal_' . $channel_id], ['text' => '🔕 Set SILENT', 'callback_data' => 'notify_silent_' . $channel_id]],
        [['text' => '⏳ Temp 1H', 'callback_data' => 'notify_tempsilent_60_' . $channel_id], ['text' => '⏳ Temp 3H', 'callback_data' => 'notify_tempsilent_180_' . $channel_id], ['text' => '⏳ Temp 8H', 'callback_data' => 'notify_tempsilent_480_' . $channel_id]],
        [['text' => '❌ Clear Temp', 'callback_data' => 'notify_clear_temp_' . $channel_id], ['text' => '⬅️ Back', 'callback_data' => 'notify_back']],
        [['text' => '❌ Close', 'callback_data' => 'notify_close']]
    ]];
    sendMessage($chat_id, $msg, $keyboard, 'HTML');
}

// ==============================
// REACTIONS FUNCTIONS
// ==============================
function get_reaction_settings($channel_id = null) {
    $settings = json_decode(file_get_contents(REACTIONS_FILE), true);
    if ($channel_id) return isset($settings[$channel_id]) ? $settings[$channel_id] : ['enabled' => false, 'reactions' => ['👍', '❤️', '🔥']];
    return $settings;
}
function save_reaction_settings($channel_id, $settings) {
    $all_settings = get_reaction_settings();
    $all_settings[$channel_id] = $settings;
    file_put_contents(REACTIONS_FILE, json_encode($all_settings, JSON_PRETTY_PRINT));
}
function enable_reactions($channel_id, $enabled) {
    $settings = get_reaction_settings($channel_id);
    $settings['enabled'] = $enabled;
    save_reaction_settings($channel_id, $settings);
}
function set_channel_reactions($channel_id, $reactions) {
    $settings = get_reaction_settings($channel_id);
    $settings['reactions'] = $reactions;
    save_reaction_settings($channel_id, $settings);
}
function auto_apply_reactions($channel_id, $message_id) {
    $settings = get_reaction_settings($channel_id);
    if (!$settings['enabled']) return false;
    $reaction_types = [];
    foreach ($settings['reactions'] as $emoji) $reaction_types[] = ['type' => 'emoji', 'emoji' => $emoji];
    return apiRequest('setMessageReaction', ['chat_id' => $channel_id, 'message_id' => $message_id, 'reaction' => json_encode($reaction_types), 'is_big' => true]);
}
function reactions_settings_menu($chat_id, $user_id) {
    global $ALL_CHANNELS;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) {
        $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name'];
        $settings = get_reaction_settings($ch['id']);
        $status = $settings['enabled'] ? '✅' : '❌';
        $keyboard['inline_keyboard'][] = [['text' => "$status $display_name", 'callback_data' => 'react_channel_' . $ch['id']]];
    }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Close', 'callback_data' => 'react_close']];
    sendMessage($chat_id, "🎯 <b>Post Reactions Settings</b>\n\nSelect a channel:", $keyboard, 'HTML');
}
function reactions_channel_menu($chat_id, $user_id, $channel_id) {
    global $ALL_CHANNELS, $AVAILABLE_REACTIONS;
    $channel_name = $channel_id;
    foreach ($ALL_CHANNELS as $ch) if ($ch['id'] == $channel_id) $channel_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name'];
    $settings = get_reaction_settings($channel_id);
    $msg = "🎯 <b>Reactions Settings</b>\n\n📺 Channel: $channel_name\n🆔 ID: <code>$channel_id</code>\n\nStatus: " . ($settings['enabled'] ? '✅ ENABLED' : '❌ DISABLED') . "\nActive: " . implode(' ', $settings['reactions']);
    $reaction_row = [];
    foreach ($AVAILABLE_REACTIONS as $emoji) {
        $is_selected = in_array($emoji, $settings['reactions']);
        $reaction_row[] = ['text' => ($is_selected ? "✅ $emoji" : "❌ $emoji"), 'callback_data' => "react_toggle_{$channel_id}_{$emoji}"];
    }
    $keyboard = ['inline_keyboard' => [
        [['text' => ($settings['enabled'] ? 'Disable' : 'Enable') . ' Reactions', 'callback_data' => 'react_enable_' . $channel_id]],
        $reaction_row,
        [['text' => '📝 Apply to Post', 'callback_data' => 'react_apply_' . $channel_id], ['text' => '🔄 Reset', 'callback_data' => 'react_reset_' . $channel_id]],
        [['text' => '⬅️ Back', 'callback_data' => 'react_back'], ['text' => '❌ Close', 'callback_data' => 'react_close']]
    ]];
    sendMessage($chat_id, $msg, $keyboard, 'HTML');
}
function reactions_apply_to_post_start($chat_id, $user_id, $channel_id) {
    global $react_step;
    $react_step[$user_id] = ['step' => 'waiting_message_id', 'channel_id' => $channel_id];
    sendMessage($chat_id, "📝 Send <b>Message ID</b> to add reactions.\n\nGet ID from @userinfobot\nSend /cancel to abort.", null, 'HTML');
}
function apply_reactions_to_post($chat_id, $user_id, $channel_id, $message_id) {
    $settings = get_reaction_settings($channel_id);
    if (!$settings['enabled']) { sendMessage($chat_id, "❌ Reactions disabled for this channel."); return false; }
    $reaction_types = [];
    foreach ($settings['reactions'] as $emoji) $reaction_types[] = ['type' => 'emoji', 'emoji' => $emoji];
    $result = apiRequest('setMessageReaction', ['chat_id' => $channel_id, 'message_id' => $message_id, 'reaction' => json_encode($reaction_types), 'is_big' => true]);
    $result_data = json_decode($result, true);
    if ($result_data && $result_data['ok']) { sendMessage($chat_id, "✅ Reactions enabled on post!"); return true; }
    else { sendMessage($chat_id, "❌ Failed: " . ($result_data['description'] ?? 'Unknown error')); return false; }
}

// ==============================
// DELIVERY LOGIC
// ==============================
function deliver_item_to_chat($chat_id, $item) {
    $channel_id = !empty($item['channel_id']) ? $item['channel_id'] : DEFAULT_CHANNEL_ID;
    $disable_notify = should_send_silent($channel_id);
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        forwardMessageWithNotify($chat_id, $channel_id, $item['message_id'], $disable_notify);
        return true;
    }
    return false;
}
function append_movie($movie_name, $message_id_raw, $channel_id = null, $channel_username = null, $channel_type = null, $date = null) {
    if (empty(trim($movie_name))) return;
    if ($date === null) $date = date('d-m-Y');
    if ($channel_id === null) $channel_id = DEFAULT_CHANNEL_ID;
    if ($channel_username === null) $channel_username = DEFAULT_CHANNEL_USERNAME;
    if ($channel_type === null) $channel_type = 'public';
    $entry = [$movie_name, $message_id_raw, $date, $channel_id, $channel_username, $channel_type];
    $handle = fopen(CSV_FILE, "a");
    fputcsv($handle, $entry);
    fclose($handle);
    global $movie_messages, $movie_cache, $waiting_users;
    $movie = strtolower(trim($movie_name));
    $item = ['movie_name' => $movie_name, 'message_id_raw' => $message_id_raw, 'date' => $date, 'channel_id' => $channel_id, 'channel_username' => $channel_username, 'channel_type' => $channel_type, 'message_id' => is_numeric($message_id_raw) ? intval($message_id_raw) : null];
    if (!isset($movie_messages[$movie])) $movie_messages[$movie] = [];
    $movie_messages[$movie][] = $item;
    $movie_cache = [];
    foreach ($waiting_users as $query => $users) {
        if (strpos($movie, $query) !== false) {
            foreach ($users as $user_data) {
                list($user_chat_id, $user_id) = $user_data;
                $disable_notify = should_send_silent($channel_id);
                forwardMessageWithNotify($user_chat_id, $channel_id, $message_id_raw, $disable_notify);
                sendMessage($user_chat_id, "✅ '$query' ab channel me add ho gaya!");
            }
            unset($waiting_users[$query]);
        }
    }
    update_stats('total_movies', 1);
}

// ==============================
// PAGINATION
// ==============================
function paginate_movies(array $all, int $page): array {
    $total = count($all);
    if ($total === 0) return ['total' => 0, 'total_pages' => 1, 'page' => 1, 'slice' => []];
    $total_pages = (int)ceil($total / ITEMS_PER_PAGE);
    $page = max(1, min($page, $total_pages));
    $start = ($page - 1) * ITEMS_PER_PAGE;
    return ['total' => $total, 'total_pages' => $total_pages, 'page' => $page, 'slice' => array_slice($all, $start, ITEMS_PER_PAGE)];
}
function forward_page_movies($chat_id, array $page_movies) {
    foreach ($page_movies as $movie) { deliver_item_to_chat($chat_id, $movie); usleep(300000); }
}
function build_totalupload_keyboard(int $page, int $total_pages): array {
    $kb = ['inline_keyboard' => []];
    $nav_row = [];
    if ($page > 1) $nav_row[] = ['text' => '⬅️ Previous', 'callback_data' => 'tu_prev_' . ($page - 1)];
    $nav_row[] = ['text' => "📄 $page/$total_pages", 'callback_data' => 'current_page'];
    if ($page < $total_pages) $nav_row[] = ['text' => 'Next ➡️', 'callback_data' => 'tu_next_' . ($page + 1)];
    if (!empty($nav_row)) $kb['inline_keyboard'][] = $nav_row;
    $kb['inline_keyboard'][] = [['text' => '🎬 Send This Page', 'callback_data' => 'tu_view_' . $page], ['text' => '🛑 Stop', 'callback_data' => 'tu_stop']];
    return $kb;
}
function totalupload_controller($chat_id, $page = 1) {
    $all = get_all_movies_list();
    if (empty($all)) { sendMessage($chat_id, "📭 Koi movies nahi mili!"); return; }
    $pg = paginate_movies($all, (int)$page);
    forward_page_movies($chat_id, $pg['slice']);
    sendMessage($chat_id, "🎬 <b>Total Uploads</b>\n\n📊 Page {$pg['page']}/{$pg['total_pages']}\n📊 Total Movies: {$pg['total']}", build_totalupload_keyboard($pg['page'], $pg['total_pages']), 'HTML');
}

// ==============================
// SEARCH FUNCTIONS
// ==============================
function smart_search($query) {
    global $movie_messages;
    $query_lower = strtolower(trim($query));
    $results = [];
    foreach ($movie_messages as $movie => $entries) {
        $score = 0;
        if ($movie == $query_lower) $score = 100;
        elseif (strpos($movie, $query_lower) !== false) $score = 80;
        else { similar_text($movie, $query_lower, $similarity); if ($similarity > 60) $score = $similarity; }
        if ($score > 0) $results[$movie] = ['score' => $score, 'count' => count($entries)];
    }
    uasort($results, function($a, $b) { return $b['score'] - $a['score']; });
    return array_slice($results, 0, 10);
}
function detect_language($text) {
    $hindi_keywords = ['फिल्म', 'मूवी', 'डाउनलोड'];
    $english_keywords = ['movie', 'download', 'watch'];
    $h = $e = 0;
    foreach ($hindi_keywords as $k) if (strpos($text, $k) !== false) $h++;
    foreach ($english_keywords as $k) if (stripos($text, $k) !== false) $e++;
    return $h > $e ? 'hindi' : 'english';
}
function send_multilingual_response($chat_id, $message_type, $language) {
    $responses = ['hindi' => ['not_found' => "😔 Yeh movie abhi available nahi hai!\n\n📝 Request: " . REQUEST_GROUP_USERNAME, 'searching' => "🔍 Dhoondh raha hoon..."], 'english' => ['not_found' => "😔 This movie isn't available yet!\n\n📝 Request: " . REQUEST_GROUP_USERNAME, 'searching' => "🔍 Searching..."]];
    sendMessage($chat_id, $responses[$language][$message_type]);
}
function advanced_search($chat_id, $query, $user_id = null) {
    global $movie_messages, $waiting_users;
    $q = strtolower(trim($query));
    if (strlen($q) < 2) { sendMessage($chat_id, "❌ Please enter at least 2 characters"); return; }
    $found = smart_search($q);
    if (!empty($found)) {
        $msg = "🔍 Found " . count($found) . " movies for '$query':\n\n";
        $i = 1;
        foreach ($found as $movie => $data) { $msg .= "$i. $movie\n"; $i++; if ($i > 10) break; }
        sendMessage($chat_id, $msg);
        $keyboard = ['inline_keyboard' => []];
        foreach (array_slice(array_keys($found), 0, 5) as $movie) $keyboard['inline_keyboard'][] = [['text' => "🎬 " . ucwords($movie), 'callback_data' => $movie]];
        sendMessage($chat_id, "🚀 Top matches:", $keyboard);
    } else {
        $lang = detect_language($query);
        send_multilingual_response($chat_id, 'not_found', $lang);
        if (!isset($waiting_users[$q])) $waiting_users[$q] = [];
        $waiting_users[$q][] = [$chat_id, $user_id ?? $chat_id];
    }
    update_stats('total_searches', 1);
}

// ==============================
// REQUEST FUNCTIONS
// ==============================
function request_movie($chat_id, $user_id, $movie_name, $user_firstname) {
    $msg = "🎬 <b>New Movie Request!</b>\n\n👤 User: $user_firstname\n🆔 ID: <code>$user_id</code>\n🍿 Movie: <code>" . htmlspecialchars($movie_name) . "</code>\n📅 Date: " . date('d-m-Y H:i:s');
    $keyboard = ['inline_keyboard' => [[['text' => '✅ Approve', 'callback_data' => 'req_add_' . $user_id . '_' . urlencode($movie_name)], ['text' => '❌ Reject', 'callback_data' => 'req_reject_' . $user_id . '_' . urlencode($movie_name)]]]];
    sendMessage(REQUEST_GROUP_ID, $msg, $keyboard, 'HTML');
    sendMessage($chat_id, "✅ Request '$movie_name' submit ho gayi!\nAdmin jald add karega.");
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if (!isset($users_data['pending_requests'][strtolower($movie_name)])) $users_data['pending_requests'][strtolower($movie_name)] = [];
    $users_data['pending_requests'][strtolower($movie_name)][] = ['user_id' => $user_id, 'chat_id' => $chat_id, 'movie' => $movie_name, 'date' => date('Y-m-d H:i:s')];
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}
function get_user_requests($chat_id, $user_id) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $user_requests = [];
    foreach ($users_data['pending_requests'] ?? [] as $movie => $requests) foreach ($requests as $req) if ($req['user_id'] == $user_id) $user_requests[] = $movie;
    if (empty($user_requests)) { sendMessage($chat_id, "📭 No pending requests!"); return; }
    $msg = "📊 Your Pending Requests:\n\n";
    foreach ($user_requests as $i => $req) $msg .= ($i+1) . ". " . htmlspecialchars($req) . "\n";
    sendMessage($chat_id, $msg, null, 'HTML');
}
function pending_requests($chat_id) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $pending = $users_data['pending_requests'] ?? [];
    if (empty($pending)) { sendMessage($chat_id, "📭 No pending requests!"); return; }
    $msg = "📊 Pending Requests:\n\n";
    $i = 1;
    foreach ($pending as $movie => $requests) { $msg .= "$i. " . htmlspecialchars($movie) . " - " . count($requests) . " users\n"; $i++; }
    sendMessage($chat_id, $msg, null, 'HTML');
}
function bulk_approve_requests($chat_id, $count = 10) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $pending = &$users_data['pending_requests'];
    if (empty($pending)) { sendMessage($chat_id, "📭 No pending requests!"); return; }
    $approved = 0;
    foreach ($pending as $movie => $requests) {
        if ($approved >= $count) break;
        foreach ($requests as $req) sendMessage($req['chat_id'], "🎉 '$movie' ab available hai! Search karo.");
        unset($pending[$movie]);
        $approved++;
    }
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
    sendMessage($chat_id, "✅ Bulk approved $approved movies!");
}
function total_upload_stats($chat_id) {
    $all = get_all_movies_list();
    $total = count($all);
    $today = date('d-m-Y');
    $today_count = 0;
    foreach ($all as $movie) if ($movie['date'] == $today) $today_count++;
    sendMessage($chat_id, "📈 Total Upload Stats\n\n🎬 Total: $total\n📅 Today: $today_count", null, 'HTML');
}
function admin_stats($chat_id) {
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $total_users = count($users_data['users'] ?? []);
    $pending = count($users_data['pending_requests'] ?? []);
    sendMessage($chat_id, "📊 Bot Stats\n\n🎬 Movies: " . ($stats['total_movies'] ?? 0) . "\n👥 Users: $total_users\n🔍 Searches: " . ($stats['total_searches'] ?? 0) . "\n📝 Pending: $pending", null, 'HTML');
}
function channel_stats($chat_id, $user_id) {
    global $ALL_CHANNELS;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) {
        $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name'];
        $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name", 'callback_data' => 'ch_stats_' . $ch['id']]];
    }
    sendMessage($chat_id, "📊 <b>Channel Statistics</b>\n\nSelect a channel:", $keyboard, 'HTML');
}
function show_channel_stats($chat_id, $channel_id) {
    global $ALL_CHANNELS;
    $channel_name = $channel_id;
    foreach ($ALL_CHANNELS as $ch) if ($ch['id'] == $channel_id) $channel_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name'];
    $all_movies = get_all_movies_list();
    $total_posts = 0; $last_7_days = 0; $last_30_days = 0;
    $today = date('d-m-Y');
    $last_7_days_ago = date('d-m-Y', strtotime('-7 days'));
    $last_30_days_ago = date('d-m-Y', strtotime('-30 days'));
    foreach ($all_movies as $movie) {
        if ($movie['channel_id'] == $channel_id) {
            $total_posts++;
            if (strtotime($movie['date']) >= strtotime($last_7_days_ago)) $last_7_days++;
            if (strtotime($movie['date']) >= strtotime($last_30_days_ago)) $last_30_days++;
        }
    }
    $msg = "📊 <b>Channel Statistics</b>\n\n📺 Channel: $channel_name\n🆔 ID: <code>$channel_id</code>\n\n📈 Post Stats:\n• Total Posts: $total_posts\n• Last 7 Days: $last_7_days\n• Last 30 Days: $last_30_days";
    $keyboard = ['inline_keyboard' => [[['text' => '🔄 Refresh', 'callback_data' => 'ch_stats_refresh_' . $channel_id], ['text' => '⬅️ Back', 'callback_data' => 'ch_stats_back']]]];
    sendMessage($chat_id, $msg, $keyboard, 'HTML');
}

// ==============================
// CREATE POST FUNCTIONS
// ==============================
function create_post_start($chat_id, $user_id) {
    global $ALL_CHANNELS, $create_post_step;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) {
        $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name'];
        $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name", 'callback_data' => 'cp_channel_' . $ch['id']]];
    }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Cancel', 'callback_data' => 'cp_cancel']];
    sendMessage($chat_id, "📝 <b>Create New Post</b>\n\nSelect channel:", $keyboard, 'HTML');
    $create_post_step[$user_id] = ['step' => 'select_channel'];
}
function create_post_step2($chat_id, $user_id, $channel_id) {
    global $create_post_step;
    $create_post_step[$user_id] = ['step' => 'select_type', 'channel_id' => $channel_id];
    $keyboard = ['inline_keyboard' => [[['text' => '📝 Text', 'callback_data' => 'cp_type_text'], ['text' => '🖼️ Photo', 'callback_data' => 'cp_type_photo'], ['text' => '🎥 Video', 'callback_data' => 'cp_type_video']], [['text' => '❌ Cancel', 'callback_data' => 'cp_cancel']]]];
    sendMessage($chat_id, "Select post type:", $keyboard, 'HTML');
}
function create_post_step3($chat_id, $user_id, $type) {
    global $create_post_step;
    $create_post_step[$user_id]['type'] = $type;
    $create_post_step[$user_id]['step'] = 'waiting_content';
    sendMessage($chat_id, "✏️ Send content. Send /cancel to abort.", null, 'HTML');
}
function execute_create_post($chat_id, $user_id, $content, $caption = '') {
    global $create_post_step;
    $step_data = $create_post_step[$user_id];
    $disable_notify = should_send_silent($step_data['channel_id']);
    $result = null;
    if ($step_data['type'] == 'text') $result = apiRequest('sendMessage', ['chat_id' => $step_data['channel_id'], 'text' => $content, 'parse_mode' => 'HTML', 'disable_notification' => $disable_notify]);
    elseif ($step_data['type'] == 'photo') $result = apiRequest('sendPhoto', ['chat_id' => $step_data['channel_id'], 'photo' => $content, 'caption' => $caption, 'parse_mode' => 'HTML', 'disable_notification' => $disable_notify], true);
    elseif ($step_data['type'] == 'video') $result = apiRequest('sendVideo', ['chat_id' => $step_data['channel_id'], 'video' => $content, 'caption' => $caption, 'parse_mode' => 'HTML', 'disable_notification' => $disable_notify], true);
    $result_data = json_decode($result, true);
    if ($result_data && $result_data['ok']) {
        $message_id = $result_data['result']['message_id'];
        append_movie($content, $message_id, $step_data['channel_id'], '', 'public', date('d-m-Y'));
        auto_apply_reactions($step_data['channel_id'], $message_id);
        sendMessage($chat_id, "✅ Post created!");
    } else sendMessage($chat_id, "❌ Failed: " . ($result_data['description'] ?? 'Unknown error'));
    unset($create_post_step[$user_id]);
}

// ==============================
// SCHEDULE POST FUNCTIONS
// ==============================
function get_schedules() { return json_decode(file_get_contents(SCHEDULES_FILE), true); }
function save_schedule($schedule) { $schedules = get_schedules(); $schedules[] = $schedule; file_put_contents(SCHEDULES_FILE, json_encode($schedules, JSON_PRETTY_PRINT)); }
function delete_schedule($index) { $schedules = get_schedules(); if (isset($schedules[$index])) { array_splice($schedules, $index, 1); file_put_contents(SCHEDULES_FILE, json_encode($schedules, JSON_PRETTY_PRINT)); return true; } return false; }
function schedule_post_start($chat_id, $user_id) {
    global $ALL_CHANNELS, $schedule_step;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) { $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name']; $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name", 'callback_data' => 'sp_channel_' . $ch['id']]]; }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Cancel', 'callback_data' => 'sp_cancel']];
    sendMessage($chat_id, "📅 <b>Schedule New Post</b>\n\nSelect channel:", $keyboard, 'HTML');
    $schedule_step[$user_id] = ['step' => 'select_channel'];
}
function schedule_post_step2($chat_id, $user_id, $channel_id) {
    global $schedule_step;
    $schedule_step[$user_id] = ['step' => 'select_type', 'channel_id' => $channel_id];
    sendMessage($chat_id, "Select post type:\nSend: text / photo / video\nSend /cancel to abort.", null, 'HTML');
}
function schedule_post_step3($chat_id, $user_id, $type) {
    global $schedule_step;
    $schedule_step[$user_id]['type'] = $type;
    $schedule_step[$user_id]['step'] = 'waiting_datetime';
    sendMessage($chat_id, "Send date & time: DD-MM-YYYY HH:MM\nExample: 25-12-2025 18:30\nSend /cancel to abort.", null, 'HTML');
}
function schedule_post_step4($chat_id, $user_id, $datetime) {
    global $schedule_step;
    $schedule_step[$user_id]['scheduled_time'] = $datetime;
    $schedule_step[$user_id]['step'] = 'waiting_content';
    sendMessage($chat_id, "✏️ Send content to schedule.\nSend /cancel to abort.", null, 'HTML');
}
function save_scheduled_post($chat_id, $user_id, $content) {
    global $schedule_step;
    $schedule = ['id' => uniqid(), 'channel_id' => $schedule_step[$user_id]['channel_id'], 'type' => $schedule_step[$user_id]['type'], 'content' => $content, 'scheduled_time' => $schedule_step[$user_id]['scheduled_time'], 'created_by' => $user_id, 'created_at' => date('Y-m-d H:i:s'), 'status' => 'pending'];
    save_schedule($schedule);
    sendMessage($chat_id, "✅ Post scheduled for {$schedule['scheduled_time']}");
    unset($schedule_step[$user_id]);
}
function view_scheduled_posts($chat_id) {
    $schedules = get_schedules();
    if (empty($schedules)) { sendMessage($chat_id, "📭 No scheduled posts."); return; }
    $msg = "📅 Scheduled Posts:\n\n";
    foreach ($schedules as $i => $sch) $msg .= ($i+1) . ". ID: {$sch['id']}\n   Time: {$sch['scheduled_time']}\n   Type: {$sch['type']}\n\n";
    sendMessage($chat_id, $msg, null, 'HTML');
}
function process_scheduled_posts() {
    $schedules = get_schedules();
    $now = date('d-m-Y H:i');
    $updated = false;
    foreach ($schedules as $i => &$sch) {
        if ($sch['status'] == 'pending' && $sch['scheduled_time'] <= $now) {
            $disable_notify = should_send_silent($sch['channel_id']);
            if ($sch['type'] == 'text') $result = apiRequest('sendMessage', ['chat_id' => $sch['channel_id'], 'text' => $sch['content'], 'parse_mode' => 'HTML', 'disable_notification' => $disable_notify]);
            $result_data = json_decode($result, true);
            if ($result_data && $result_data['ok']) { $sch['status'] = 'posted'; $sch['posted_at'] = date('Y-m-d H:i:s'); append_movie($sch['content'], $result_data['result']['message_id'], $sch['channel_id'], '', 'public', date('d-m-Y')); }
            else $sch['status'] = 'failed';
            $updated = true;
        }
    }
    if ($updated) file_put_contents(SCHEDULES_FILE, json_encode($schedules, JSON_PRETTY_PRINT));
}

// ==============================
// EDIT POST FUNCTIONS
// ==============================
function edit_post_start($chat_id, $user_id) {
    global $ALL_CHANNELS, $edit_step;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) { $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name']; $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name", 'callback_data' => 'ep_channel_' . $ch['id']]]; }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Cancel', 'callback_data' => 'ep_cancel']];
    sendMessage($chat_id, "✏️ <b>Edit Post</b>\n\nSelect channel:", $keyboard, 'HTML');
    $edit_step[$user_id] = ['step' => 'select_channel'];
}
function edit_post_step2($chat_id, $user_id, $channel_id) {
    global $edit_step;
    $edit_step[$user_id] = ['step' => 'get_message_id', 'channel_id' => $channel_id];
    sendMessage($chat_id, "📝 Send Message ID to edit.\nSend /cancel to abort.", null, 'HTML');
}
function edit_post_step3($chat_id, $user_id, $channel_id, $message_id) {
    global $edit_step;
    $edit_step[$user_id] = ['step' => 'get_new_text', 'channel_id' => $channel_id, 'message_id' => $message_id];
    sendMessage($chat_id, "✏️ Send new text for this post.\nSend /cancel to abort.", null, 'HTML');
}
function execute_edit_post($chat_id, $user_id, $channel_id, $message_id, $new_text) {
    $result = apiRequest('editMessageText', ['chat_id' => $channel_id, 'message_id' => $message_id, 'text' => $new_text, 'parse_mode' => 'HTML']);
    if (json_decode($result, true)['ok']) { sendMessage($chat_id, "✅ Post edited!"); remove_movie_from_csv($message_id, $channel_id); append_movie($new_text, $message_id, $channel_id, '', 'public', date('d-m-Y')); }
    else sendMessage($chat_id, "❌ Failed to edit.");
}

// ==============================
// DELETE POST FUNCTIONS
// ==============================
function delete_message_start($chat_id, $user_id) {
    global $ALL_CHANNELS, $delete_step;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) { $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name']; $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name", 'callback_data' => 'del_channel_' . $ch['id']]]; }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Cancel', 'callback_data' => 'del_cancel']];
    sendMessage($chat_id, "🗑️ <b>Delete Message</b>\n\nSelect channel:", $keyboard, 'HTML');
    $delete_step[$user_id] = ['step' => 'select_channel'];
}
function delete_message_step2($chat_id, $user_id, $channel_id) {
    global $delete_step;
    $delete_step[$user_id] = ['step' => 'get_message_id', 'channel_id' => $channel_id];
    sendMessage($chat_id, "📝 Send Message ID to delete.\nSend /cancel to abort.", null, 'HTML');
}
function delete_message_by_id($chat_id, $user_id, $channel_id, $message_id) {
    $result = apiRequest('deleteMessage', ['chat_id' => $channel_id, 'message_id' => $message_id]);
    if (json_decode($result, true)['ok']) { sendMessage($chat_id, "✅ Message deleted!"); remove_movie_from_csv($message_id, $channel_id); return true; }
    else { sendMessage($chat_id, "❌ Failed to delete."); return false; }
}
function bulk_delete_messages($chat_id, $user_id, $channel_id, $count = 10) {
    $all_movies = get_all_movies_list();
    $channel_movies = [];
    foreach ($all_movies as $movie) if ($movie['channel_id'] == $channel_id && $movie['message_id']) $channel_movies[] = $movie;
    usort($channel_movies, function($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });
    $to_delete = array_slice($channel_movies, 0, $count);
    $deleted = 0;
    foreach ($to_delete as $movie) { if (delete_message_by_id($chat_id, $user_id, $channel_id, $movie['message_id'])) $deleted++; usleep(200000); }
    sendMessage($chat_id, "✅ Bulk delete complete: $deleted deleted.");
}
function bulk_delete_start($chat_id, $user_id) {
    global $ALL_CHANNELS, $delete_step;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) { $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name']; $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name", 'callback_data' => 'bulk_channel_' . $ch['id']]]; }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Cancel', 'callback_data' => 'bulk_cancel']];
    sendMessage($chat_id, "🗑️ <b>Bulk Delete</b>\n\nSelect channel:", $keyboard, 'HTML');
    $delete_step[$user_id] = ['step' => 'bulk_channel'];
}
function bulk_delete_step2($chat_id, $user_id, $channel_id) {
    global $delete_step;
    $delete_step[$user_id] = ['step' => 'bulk_count', 'channel_id' => $channel_id];
    $keyboard = ['inline_keyboard' => [[['text' => '5', 'callback_data' => 'bulk_5_' . $channel_id], ['text' => '10', 'callback_data' => 'bulk_10_' . $channel_id], ['text' => '25', 'callback_data' => 'bulk_25_' . $channel_id]], [['text' => '50', 'callback_data' => 'bulk_50_' . $channel_id], ['text' => '100', 'callback_data' => 'bulk_100_' . $channel_id], ['text' => '❌ Cancel', 'callback_data' => 'bulk_cancel']]]];
    sendMessage($chat_id, "How many recent messages to delete?", $keyboard, 'HTML');
}

// ==============================
// ADD COMMENTS FUNCTIONS
// ==============================
function get_comments($post_id) { $comments = json_decode(file_get_contents(COMMENTS_FILE), true); return isset($comments[$post_id]) ? $comments[$post_id] : []; }
function add_comment($post_id, $user_id, $username, $comment, $reply_to = null) {
    $comments = json_decode(file_get_contents(COMMENTS_FILE), true);
    $new_comment = ['id' => uniqid(), 'post_id' => $post_id, 'user_id' => $user_id, 'username' => $username, 'comment' => htmlspecialchars($comment), 'reply_to' => $reply_to, 'timestamp' => date('Y-m-d H:i:s')];
    if (!isset($comments[$post_id])) $comments[$post_id] = [];
    $comments[$post_id][] = $new_comment;
    file_put_contents(COMMENTS_FILE, json_encode($comments, JSON_PRETTY_PRINT));
    return $new_comment;
}
function add_comments_start($chat_id, $user_id) {
    global $ALL_CHANNELS, $comment_step;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) { $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name']; $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name", 'callback_data' => 'ac_channel_' . $ch['id']]]; }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Cancel', 'callback_data' => 'ac_cancel']];
    sendMessage($chat_id, "💬 <b>Add Comments Section</b>\n\nSelect channel:", $keyboard, 'HTML');
    $comment_step[$user_id] = ['step' => 'select_channel'];
}
function add_comments_step2($chat_id, $user_id, $channel_id) {
    global $comment_step;
    $comment_step[$user_id] = ['step' => 'get_message_id', 'channel_id' => $channel_id];
    sendMessage($chat_id, "📝 Send Message ID to add comments.\nSend /cancel to abort.", null, 'HTML');
}
function add_comments_to_post($chat_id, $user_id, $channel_id, $message_id) {
    $post_id = $channel_id . '_' . $message_id;
    $keyboard = ['inline_keyboard' => [[['text' => '💬 View Comments', 'callback_data' => 'view_comments_' . $post_id . '_' . $message_id]]]];
    editMessageReplyMarkup($channel_id, $message_id, $keyboard);
    sendMessage($chat_id, "✅ Comments section added!");
}
function view_comments($chat_id, $post_id, $message_id = null) {
    $comments = get_comments($post_id);
    $msg = "💬 Comments (" . count($comments) . ")\n\n";
    foreach ($comments as $c) $msg .= "👤 {$c['username']}\n📝 {$c['comment']}\n🕒 {$c['timestamp']}\n\n";
    if (empty($comments)) $msg .= "📭 No comments yet.";
    $keyboard = ['inline_keyboard' => [[['text' => '✏️ Add Comment', 'callback_data' => 'comment_add_' . $post_id]], [['text' => '🔄 Refresh', 'callback_data' => 'comment_refresh_' . $post_id . '_' . $message_id], ['text' => '❌ Close', 'callback_data' => 'comment_close']]]];
    if ($message_id) editMessageText($chat_id, $message_id, $msg, $keyboard, 'HTML');
    else sendMessage($chat_id, $msg, $keyboard, 'HTML');
}
function add_comment_start($chat_id, $user_id, $post_id) {
    global $comment_step;
    $comment_step[$user_id] = ['step' => 'waiting_comment', 'post_id' => $post_id];
    sendMessage($chat_id, "✏️ Write your comment:\nSend /cancel to abort.", null, 'HTML');
}
function submit_comment($chat_id, $user_id, $username, $comment_text, $post_id) {
    if (strlen(trim($comment_text)) < 2) { sendMessage($chat_id, "❌ Comment too short."); return false; }
    add_comment($post_id, $user_id, $username, $comment_text);
    sendMessage($chat_id, "✅ Comment added!");
    return true;
}

// ==============================
// BUTTONS FUNCTIONS
// ==============================
$BUTTON_TEMPLATES = ['join' => [['text' => '📢 Join Channel', 'url' => ''], ['text' => '📺 Join Serials', 'url' => '']], 'watch' => [['text' => '🎬 Watch Now', 'url' => ''], ['text' => '📥 Download', 'url' => '']], 'social' => [['text' => '💬 Request Group', 'url' => ''], ['text' => '🤖 Bot', 'url' => '']]];
function add_buttons_start($chat_id, $user_id) {
    global $ALL_CHANNELS, $buttons_step;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) { $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name']; $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name", 'callback_data' => 'btn_channel_' . $ch['id']]]; }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Cancel', 'callback_data' => 'btn_cancel']];
    sendMessage($chat_id, "🔘 <b>Add URL Buttons</b>\n\nSelect channel:", $keyboard, 'HTML');
    $buttons_step[$user_id] = ['step' => 'select_channel'];
}
function add_buttons_step2($chat_id, $user_id, $channel_id) {
    global $buttons_step;
    $buttons_step[$user_id] = ['step' => 'get_message_id', 'channel_id' => $channel_id];
    sendMessage($chat_id, "📝 Send Message ID to add buttons.\nSend /cancel to abort.", null, 'HTML');
}
function add_buttons_step3($chat_id, $user_id, $channel_id, $message_id) {
    global $buttons_step, $BUTTON_TEMPLATES;
    $buttons_step[$user_id] = ['step' => 'select_template', 'channel_id' => $channel_id, 'message_id' => $message_id];
    $keyboard = ['inline_keyboard' => [[['text' => '📢 Join', 'callback_data' => 'btn_template_join'], ['text' => '🎬 Watch', 'callback_data' => 'btn_template_watch'], ['text' => '💬 Social', 'callback_data' => 'btn_template_social']], [['text' => '✏️ Custom', 'callback_data' => 'btn_custom'], ['text' => '❌ Cancel', 'callback_data' => 'btn_cancel']]]];
    sendMessage($chat_id, "Select button template:", $keyboard, 'HTML');
}
function apply_buttons_to_post($chat_id, $user_id, $channel_id, $message_id, $buttons) {
    $result = editMessageReplyMarkup($channel_id, $message_id, ['inline_keyboard' => $buttons]);
    if (json_decode($result, true)['ok']) sendMessage($chat_id, "✅ Buttons added!");
    else sendMessage($chat_id, "❌ Failed to add buttons.");
}

// ==============================
// DISCUSSION GROUP FUNCTIONS
// ==============================
function setup_discussion_start($chat_id, $user_id) {
    global $ALL_CHANNELS, $discuss_step;
    $keyboard = ['inline_keyboard' => []];
    foreach ($ALL_CHANNELS as $ch) { $display_name = $ch['type'] == 'public' ? $ch['username'] : $ch['name']; $keyboard['inline_keyboard'][] = [['text' => "📺 $display_name", 'callback_data' => 'discuss_channel_' . $ch['id']]]; }
    $keyboard['inline_keyboard'][] = [['text' => '❌ Close', 'callback_data' => 'discuss_close']];
    sendMessage($chat_id, "💬 <b>Native Comments Setup</b>\n\nSelect channel:", $keyboard, 'HTML');
}
function setup_discussion_group($chat_id, $user_id, $channel_id) {
    $existing = json_decode(file_get_contents(DISCUSSION_FILE), true)[$channel_id] ?? null;
    $msg = "💬 Native Comments Setup\n\nChannel: $channel_id\n" . ($existing ? "Linked to: {$existing['group_id']}" : "No group linked");
    $keyboard = ['inline_keyboard' => [[['text' => '➕ Link Group', 'callback_data' => 'discuss_link_' . $channel_id]], [['text' => '❌ Remove', 'callback_data' => 'discuss_remove_' . $channel_id]], [['text' => '⬅️ Back', 'callback_data' => 'discuss_back'], ['text' => '❌ Close', 'callback_data' => 'discuss_close']]]];
    sendMessage($chat_id, $msg, $keyboard, 'HTML');
}
function discuss_link_group_start($chat_id, $user_id, $channel_id) {
    global $discuss_step;
    $discuss_step[$user_id] = ['step' => 'waiting_group_id', 'channel_id' => $channel_id];
    sendMessage($chat_id, "📝 Send Group ID (starts with -100)\nSend /cancel to abort.", null, 'HTML');
}
function save_discussion_link($chat_id, $user_id, $channel_id, $group_id) {
    $groups = json_decode(file_get_contents(DISCUSSION_FILE), true);
    $groups[$channel_id] = ['group_id' => $group_id, 'linked_at' => date('Y-m-d H:i:s')];
    file_put_contents(DISCUSSION_FILE, json_encode($groups, JSON_PRETTY_PRINT));
    sendMessage($chat_id, "✅ Discussion group linked!");
}

// ==============================
// MAIN WEBHOOK HANDLER
// ==============================
$update = json_decode(file_get_contents('php://input'), true);
if ($update) {
    get_cached_movies();
    process_scheduled_posts();
    
    // Channel posts
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        if (in_array($chat_id, $ALL_CHANNEL_IDS)) {
            $channel_username = ''; $channel_type = 'private';
            foreach ($ALL_CHANNELS as $ch) if ($ch['id'] == $chat_id) { $channel_username = $ch['username'] ?? 'Private'; $channel_type = $ch['type']; break; }
            $text = $message['caption'] ?? $message['text'] ?? $message['document']['file_name'] ?? 'Media';
            if (!empty(trim($text))) { append_movie($text, $message_id, $chat_id, $channel_username, $channel_type, date('d-m-Y')); auto_apply_reactions($chat_id, $message_id); }
        }
    }
    
    // Messages
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = $message['text'] ?? '';
        
        // Save user
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (!isset($users_data['users'][$user_id])) {
            $users_data['users'][$user_id] = ['first_name' => $message['from']['first_name'] ?? '', 'username' => $message['from']['username'] ?? '', 'joined' => date('Y-m-d H:i:s')];
            file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            update_stats('total_users', 1);
        }
        
        // Quick delete by reply
        if (isset($message['reply_to_message']) && ($text == '/del' || $text == '/delete') && $user_id == ADMIN_ID) {
            $replied = $message['reply_to_message'];
            delete_message_by_id($chat_id, $user_id, $replied['chat']['id'], $replied['message_id']);
            break;
        }
        
        // Commands
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = $parts[0];
            
            switch ($command) {
                case '/start':
                    $welcome = "🎬 Welcome to Entertainment Tadka!\n\n📢 How to use:\n• Type any movie name\n• Partial names work\n\n🔍 Examples:\n• Mandala Murders 2025\n• Zebra 2024\n• Squid Game\n\n❌ Don't type technical questions\n\n💬 Need help? Use /help";
                    $keyboard = ['inline_keyboard' => [
                        [['text' => '🍿 Main', 'url' => 'https://t.me/' . ltrim(CHANNEL_1_USERNAME, '@')], ['text' => '📺 Serial', 'url' => 'https://t.me/' . ltrim(CHANNEL_2_USERNAME, '@')], ['text' => '🎭 Theater', 'url' => 'https://t.me/' . ltrim(CHANNEL_3_USERNAME, '@')]],
                        [['text' => '📥 Requests', 'url' => 'https://t.me/' . ltrim(REQUEST_GROUP_USERNAME, '@')], ['text' => '🔒 Backup', 'url' => 'https://t.me/' . ltrim(CHANNEL_4_USERNAME, '@')], ['text' => '🤖 Bot', 'url' => 'https://t.me/EntertainmentTadkaBot']],
                        [['text' => '📁 All Movies', 'callback_data' => 'user_totaluploads'], ['text' => '📝 My Requests', 'callback_data' => 'user_myrequests'], ['text' => '❓ Help', 'callback_data' => 'user_help']]
                    ]];
                    if ($user_id == ADMIN_ID) {
                        $keyboard['inline_keyboard'][] = [['text' => '📝 Create', 'callback_data' => 'admin_createpost'], ['text' => '📅 Schedule', 'callback_data' => 'admin_schedulepost'], ['text' => '✏️ Edit', 'callback_data' => 'admin_editpost']];
                        $keyboard['inline_keyboard'][] = [['text' => '🗑️ Delete', 'callback_data' => 'admin_delete'], ['text' => '🗑️ Bulk', 'callback_data' => 'admin_bulkdelete'], ['text' => '💬 Comments', 'callback_data' => 'admin_addcomments']];
                        $keyboard['inline_keyboard'][] = [['text' => '🎯 Reactions', 'callback_data' => 'admin_setreactions'], ['text' => '🔘 Buttons', 'callback_data' => 'admin_addbuttons'], ['text' => '💬 Native', 'callback_data' => 'admin_setupdiscussion']];
                        $keyboard['inline_keyboard'][] = [['text' => '📊 Stats', 'callback_data' => 'admin_channelstats'], ['text' => '🔔 Notify', 'callback_data' => 'admin_notify'], ['text' => '📋 Scheduled', 'callback_data' => 'admin_viewschedules']];
                        $keyboard['inline_keyboard'][] = [['text' => '⚙️ Settings', 'callback_data' => 'admin_settings'], ['text' => '📝 Pending', 'callback_data' => 'admin_pending'], ['text' => '✅ Approve', 'callback_data' => 'admin_bulk_10']];
                        $keyboard['inline_keyboard'][] = [['text' => '🔇 Silent', 'callback_data' => 'admin_silent_quick'], ['text' => '🔊 Normal', 'callback_data' => 'admin_normal_quick'], ['text' => '📈 Uploads', 'callback_data' => 'admin_total_upload']];
                        $keyboard['inline_keyboard'][] = [['text' => '👥 Users', 'callback_data' => 'admin_total_users'], ['text' => '🎬 Movies', 'callback_data' => 'admin_total_movies'], ['text' => '🔍 Searches', 'callback_data' => 'admin_total_searches']];
                    }
                    sendMessage($chat_id, $welcome, $keyboard, 'HTML');
                    break;
                case '/help':
                    sendMessage($chat_id, "📁 Commands:\n• /totaluploads - All movies\n• /request movie - Request movie\n• /myrequests - Your requests\n\n👑 Admin:\n• /createpost, /schedulepost, /editpost\n• /delete, /bulkdelete\n• /addcomments, /setupdiscussion\n• /setreactions, /addbuttons\n• /channelstats, /notify, /settings", null, 'HTML');
                    break;
                case '/totalupload': case '/totaluploads': totalupload_controller($chat_id, 1); break;
                case '/request': if (isset($parts[1])) request_movie($chat_id, $user_id, trim(implode(' ', array_slice($parts, 1))), $message['from']['first_name'] ?? 'User'); else sendMessage($chat_id, "Usage: /request <movie name>"); break;
                case '/myrequests': get_user_requests($chat_id, $user_id); break;
                case '/createpost': if ($user_id == ADMIN_ID) create_post_start($chat_id, $user_id); break;
                case '/schedulepost': if ($user_id == ADMIN_ID) schedule_post_start($chat_id, $user_id); break;
                case '/editpost': if ($user_id == ADMIN_ID) edit_post_start($chat_id, $user_id); break;
                case '/delete': case '/del': if ($user_id == ADMIN_ID) delete_message_start($chat_id, $user_id); break;
                case '/bulkdelete': if ($user_id == ADMIN_ID) bulk_delete_start($chat_id, $user_id); break;
                case '/addcomments': if ($user_id == ADMIN_ID) add_comments_start($chat_id, $user_id); break;
                case '/setupdiscussion': if ($user_id == ADMIN_ID) setup_discussion_start($chat_id, $user_id); break;
                case '/setreactions': if ($user_id == ADMIN_ID) reactions_settings_menu($chat_id, $user_id); break;
                case '/addbuttons': if ($user_id == ADMIN_ID) add_buttons_start($chat_id, $user_id); break;
                case '/channelstats': if ($user_id == ADMIN_ID) channel_stats($chat_id, $user_id); break;
                case '/notify': if ($user_id == ADMIN_ID) notify_settings_menu($chat_id, $user_id); break;
                case '/viewschedules': if ($user_id == ADMIN_ID) view_scheduled_posts($chat_id); break;
                case '/settings': if ($user_id == ADMIN_ID) sendMessage($chat_id, "⚙️ Settings - Use buttons in /start menu", null, 'HTML'); break;
                case '/pending_request': if ($user_id == ADMIN_ID) pending_requests($chat_id); break;
                case '/bulk_approve': if ($user_id == ADMIN_ID) { $count = isset($parts[1]) ? intval($parts[1]) : 10; bulk_approve_requests($chat_id, $count); } break;
                case '/total_upload': if ($user_id == ADMIN_ID) total_upload_stats($chat_id); break;
                case '/stats': if ($user_id == ADMIN_ID) admin_stats($chat_id); break;
                case '/silent': if ($user_id == ADMIN_ID && isset($message['reply_to_message'])) set_channel_notification($message['reply_to_message']['chat']['id'], false); break;
                case '/normal': if ($user_id == ADMIN_ID && isset($message['reply_to_message'])) set_channel_notification($message['reply_to_message']['chat']['id'], true); break;
            }
        } elseif (!empty(trim($text))) {
            send_multilingual_response($chat_id, 'searching', detect_language($text));
            advanced_search($chat_id, $text, $user_id);
        }
        
        // Handle step inputs
        if (isset($create_post_step[$user_id]) && $create_post_step[$user_id]['step'] == 'waiting_content') { execute_create_post($chat_id, $user_id, $text, ''); break; }
        if (isset($schedule_step[$user_id]) && $schedule_step[$user_id]['step'] == 'waiting_datetime') { schedule_post_step4($chat_id, $user_id, $text); break; }
        if (isset($schedule_step[$user_id]) && $schedule_step[$user_id]['step'] == 'waiting_content') { save_scheduled_post($chat_id, $user_id, $text); break; }
        if (isset($edit_step[$user_id]) && $edit_step[$user_id]['step'] == 'get_message_id') { edit_post_step3($chat_id, $user_id, $edit_step[$user_id]['channel_id'], intval($text)); unset($edit_step[$user_id]); break; }
        if (isset($edit_step[$user_id]) && $edit_step[$user_id]['step'] == 'get_new_text') { execute_edit_post($chat_id, $user_id, $edit_step[$user_id]['channel_id'], $edit_step[$user_id]['message_id'], $text); unset($edit_step[$user_id]); break; }
        if (isset($delete_step[$user_id]) && $delete_step[$user_id]['step'] == 'get_message_id') { delete_message_by_id($chat_id, $user_id, $delete_step[$user_id]['channel_id'], intval($text)); unset($delete_step[$user_id]); break; }
        if (isset($comment_step[$user_id]) && $comment_step[$user_id]['step'] == 'waiting_comment') { submit_comment($chat_id, $user_id, $message['from']['first_name'] ?? 'User', $text, $comment_step[$user_id]['post_id']); unset($comment_step[$user_id]); break; }
        if (isset($buttons_step[$user_id]) && $buttons_step[$user_id]['step'] == 'get_message_id') { add_buttons_step3($chat_id, $user_id, $buttons_step[$user_id]['channel_id'], intval($text)); unset($buttons_step[$user_id]); break; }
        if (isset($react_step[$user_id]) && $react_step[$user_id]['step'] == 'waiting_message_id') { apply_reactions_to_post($chat_id, $user_id, $react_step[$user_id]['channel_id'], intval($text)); unset($react_step[$user_id]); break; }
        if (isset($discuss_step[$user_id]) && $discuss_step[$user_id]['step'] == 'waiting_group_id') { save_discussion_link($chat_id, $user_id, $discuss_step[$user_id]['channel_id'], $text); unset($discuss_step[$user_id]); break; }
    }
    
    // Callbacks
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $chat_id = $query['message']['chat']['id'];
        $user_id = $query['from']['id'];
        $data = $query['data'];
        
        global $movie_messages;
        $movie_lower = strtolower($data);
        
        if (isset($movie_messages[$movie_lower])) {
            foreach ($movie_messages[$movie_lower] as $entry) deliver_item_to_chat($chat_id, $entry);
            sendMessage($chat_id, "✅ Movie forwarded!");
            answerCallbackQuery($query['id'], "Sent!");
        }
        // User buttons
        elseif ($data === 'user_totaluploads') { totalupload_controller($chat_id, 1); answerCallbackQuery($query['id'], "Loading..."); }
        elseif ($data === 'user_myrequests') { get_user_requests($chat_id, $user_id); answerCallbackQuery($query['id'], "Your requests"); }
        elseif ($data === 'user_help') { sendMessage($chat_id, "Use /help for commands", null, 'HTML'); answerCallbackQuery($query['id'], "Help"); }
        // Admin buttons
        elseif ($data === 'admin_createpost') { create_post_start($chat_id, $user_id); answerCallbackQuery($query['id'], "Create post"); }
        elseif ($data === 'admin_schedulepost') { schedule_post_start($chat_id, $user_id); answerCallbackQuery($query['id'], "Schedule post"); }
        elseif ($data === 'admin_editpost') { edit_post_start($chat_id, $user_id); answerCallbackQuery($query['id'], "Edit post"); }
        elseif ($data === 'admin_delete') { delete_message_start($chat_id, $user_id); answerCallbackQuery($query['id'], "Delete message"); }
        elseif ($data === 'admin_bulkdelete') { bulk_delete_start($chat_id, $user_id); answerCallbackQuery($query['id'], "Bulk delete"); }
        elseif ($data === 'admin_addcomments') { add_comments_start($chat_id, $user_id); answerCallbackQuery($query['id'], "Add comments"); }
        elseif ($data === 'admin_setreactions') { reactions_settings_menu($chat_id, $user_id); answerCallbackQuery($query['id'], "Reactions"); }
        elseif ($data === 'admin_addbuttons') { add_buttons_start($chat_id, $user_id); answerCallbackQuery($query['id'], "Add buttons"); }
        elseif ($data === 'admin_setupdiscussion') { setup_discussion_start($chat_id, $user_id); answerCallbackQuery($query['id'], "Native comments"); }
        elseif ($data === 'admin_channelstats') { channel_stats($chat_id, $user_id); answerCallbackQuery($query['id'], "Channel stats"); }
        elseif ($data === 'admin_notify') { notify_settings_menu($chat_id, $user_id); answerCallbackQuery($query['id'], "Notify settings"); }
        elseif ($data === 'admin_viewschedules') { view_scheduled_posts($chat_id); answerCallbackQuery($query['id'], "Scheduled posts"); }
        elseif ($data === 'admin_settings') { sendMessage($chat_id, "⚙️ Settings - Use /start menu buttons", null, 'HTML'); answerCallbackQuery($query['id'], "Settings"); }
        elseif ($data === 'admin_pending') { pending_requests($chat_id); answerCallbackQuery($query['id'], "Pending requests"); }
        elseif ($data === 'admin_bulk_10') { bulk_approve_requests($chat_id, 10); answerCallbackQuery($query['id'], "Bulk approve"); }
        elseif ($data === 'admin_silent_quick') { sendMessage($chat_id, "🔇 Reply to channel post with /silent", null, 'HTML'); answerCallbackQuery($query['id'], "Silent mode"); }
        elseif ($data === 'admin_normal_quick') { sendMessage($chat_id, "🔊 Reply to channel post with /normal", null, 'HTML'); answerCallbackQuery($query['id'], "Normal mode"); }
        elseif ($data === 'admin_total_upload') { total_upload_stats($chat_id); answerCallbackQuery($query['id'], "Upload stats"); }
        elseif ($data === 'admin_total_users') { $users_data = json_decode(file_get_contents(USERS_FILE), true); sendMessage($chat_id, "👥 Users: " . count($users_data['users'] ?? [])); answerCallbackQuery($query['id'], "Users"); }
        elseif ($data === 'admin_total_movies') { $stats = get_stats(); sendMessage($chat_id, "🎬 Movies: " . ($stats['total_movies'] ?? 0)); answerCallbackQuery($query['id'], "Movies"); }
        elseif ($data === 'admin_total_searches') { $stats = get_stats(); sendMessage($chat_id, "🔍 Searches: " . ($stats['total_searches'] ?? 0)); answerCallbackQuery($query['id'], "Searches"); }
        // Pagination
        elseif (strpos($data, 'tu_prev_') === 0) { totalupload_controller($chat_id, (int)str_replace('tu_prev_', '', $data)); answerCallbackQuery($query['id'], "Previous"); }
        elseif (strpos($data, 'tu_next_') === 0) { totalupload_controller($chat_id, (int)str_replace('tu_next_', '', $data)); answerCallbackQuery($query['id'], "Next"); }
        elseif (strpos($data, 'tu_view_') === 0) { $page = (int)str_replace('tu_view_', '', $data); $all = get_all_movies_list(); forward_page_movies($chat_id, paginate_movies($all, $page)['slice']); answerCallbackQuery($query['id'], "Sent"); }
        elseif ($data === 'tu_stop') { sendMessage($chat_id, "Stopped."); answerCallbackQuery($query['id'], "Stopped"); }
        // Comments
        elseif (strpos($data, 'view_comments_') === 0) { $parts = explode('_', $data); $post_id = $parts[2] . '_' . $parts[3]; view_comments($chat_id, $post_id, $parts[4] ?? null); answerCallbackQuery($query['id'], "Comments"); }
        elseif (strpos($data, 'comment_add_') === 0) { $post_id = str_replace('comment_add_', '', $data); add_comment_start($chat_id, $user_id, $post_id); answerCallbackQuery($query['id'], "Write comment"); }
        elseif (strpos($data, 'comment_refresh_') === 0) { $parts = explode('_', $data); $post_id = $parts[2] . '_' . $parts[3]; view_comments($chat_id, $post_id, $parts[4] ?? null); answerCallbackQuery($query['id'], "Refreshed"); }
        elseif ($data === 'comment_close') { answerCallbackQuery($query['id'], "Closed"); }
        // Reactions callbacks
        elseif (strpos($data, 'react_channel_') === 0) { $channel_id = str_replace('react_channel_', '', $data); reactions_channel_menu($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Loading"); }
        elseif (strpos($data, 'react_enable_') === 0) { $channel_id = str_replace('react_enable_', '', $data); $settings = get_reaction_settings($channel_id); enable_reactions($channel_id, !$settings['enabled']); reactions_channel_menu($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Toggled"); }
        elseif (strpos($data, 'react_toggle_') === 0) { $parts = explode('_', $data); $channel_id = $parts[2]; $emoji = $parts[3]; $settings = get_reaction_settings($channel_id); if (in_array($emoji, $settings['reactions'])) $settings['reactions'] = array_values(array_diff($settings['reactions'], [$emoji])); else $settings['reactions'][] = $emoji; save_reaction_settings($channel_id, $settings); reactions_channel_menu($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Toggled"); }
        elseif (strpos($data, 'react_apply_') === 0) { $channel_id = str_replace('react_apply_', '', $data); reactions_apply_to_post_start($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Send message ID"); }
        elseif ($data === 'react_back') { reactions_settings_menu($chat_id, $user_id); answerCallbackQuery($query['id'], "Back"); }
        elseif ($data === 'react_close') { answerCallbackQuery($query['id'], "Closed"); }
        // Notify callbacks
        elseif (strpos($data, 'notify_channel_') === 0) { $channel_id = str_replace('notify_channel_', '', $data); notify_channel_menu($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Loading"); }
        elseif (strpos($data, 'notify_normal_') === 0) { $channel_id = str_replace('notify_normal_', '', $data); set_channel_notification($channel_id, true); notify_channel_menu($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Normal mode"); }
        elseif (strpos($data, 'notify_silent_') === 0) { $channel_id = str_replace('notify_silent_', '', $data); set_channel_notification($channel_id, false); notify_channel_menu($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Silent mode"); }
        elseif (strpos($data, 'notify_tempsilent_') === 0) { $parts = explode('_', $data); $duration = intval($parts[2]); $channel_id = $parts[3]; set_temp_silent($channel_id, $duration); notify_channel_menu($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Temp silent"); }
        elseif (strpos($data, 'notify_clear_temp_') === 0) { $channel_id = str_replace('notify_clear_temp_', '', $data); clear_temp_silent($channel_id); notify_channel_menu($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Cleared"); }
        elseif ($data === 'notify_back') { notify_settings_menu($chat_id, $user_id); answerCallbackQuery($query['id'], "Back"); }
        elseif ($data === 'notify_close') { answerCallbackQuery($query['id'], "Closed"); }
        // Create post callbacks
        elseif (strpos($data, 'cp_channel_') === 0) { $channel_id = str_replace('cp_channel_', '', $data); create_post_step2($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Channel selected"); }
        elseif (strpos($data, 'cp_type_') === 0) { $type = str_replace('cp_type_', '', $data); create_post_step3($chat_id, $user_id, $type); answerCallbackQuery($query['id'], "Type selected"); }
        elseif ($data === 'cp_cancel') { unset($create_post_step[$user_id]); sendMessage($chat_id, "Cancelled."); answerCallbackQuery($query['id'], "Cancelled"); }
        // Schedule callbacks
        elseif (strpos($data, 'sp_channel_') === 0) { $channel_id = str_replace('sp_channel_', '', $data); schedule_post_step2($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Channel selected"); }
        elseif ($data === 'sp_cancel') { unset($schedule_step[$user_id]); sendMessage($chat_id, "Cancelled."); answerCallbackQuery($query['id'], "Cancelled"); }
        // Edit callbacks
        elseif (strpos($data, 'ep_channel_') === 0) { $channel_id = str_replace('ep_channel_', '', $data); edit_post_step2($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Channel selected"); }
        elseif ($data === 'ep_cancel') { unset($edit_step[$user_id]); sendMessage($chat_id, "Cancelled."); answerCallbackQuery($query['id'], "Cancelled"); }
        // Delete callbacks
        elseif (strpos($data, 'del_channel_') === 0) { $channel_id = str_replace('del_channel_', '', $data); delete_message_step2($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Channel selected"); }
        elseif ($data === 'del_cancel') { unset($delete_step[$user_id]); sendMessage($chat_id, "Cancelled."); answerCallbackQuery($query['id'], "Cancelled"); }
        elseif (strpos($data, 'bulk_channel_') === 0) { $channel_id = str_replace('bulk_channel_', '', $data); bulk_delete_step2($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Channel selected"); }
        elseif (strpos($data, 'bulk_') === 0 && strpos($data, 'bulk_channel_') !== 0 && $data !== 'bulk_cancel') { $parts = explode('_', $data); $count = intval($parts[1]); $channel_id = $parts[2]; bulk_delete_messages($chat_id, $user_id, $channel_id, $count); unset($delete_step[$user_id]); answerCallbackQuery($query['id'], "Deleting"); }
        elseif ($data === 'bulk_cancel') { unset($delete_step[$user_id]); sendMessage($chat_id, "Cancelled."); answerCallbackQuery($query['id'], "Cancelled"); }
        // Add comments callbacks
        elseif (strpos($data, 'ac_channel_') === 0) { $channel_id = str_replace('ac_channel_', '', $data); add_comments_step2($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Channel selected"); }
        elseif ($data === 'ac_cancel') { unset($comment_step[$user_id]); sendMessage($chat_id, "Cancelled."); answerCallbackQuery($query['id'], "Cancelled"); }
        // Buttons callbacks
        elseif (strpos($data, 'btn_channel_') === 0) { $channel_id = str_replace('btn_channel_', '', $data); add_buttons_step2($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Channel selected"); }
        elseif ($data === 'btn_cancel') { unset($buttons_step[$user_id]); sendMessage($chat_id, "Cancelled."); answerCallbackQuery($query['id'], "Cancelled"); }
        // Discussion callbacks
        elseif (strpos($data, 'discuss_channel_') === 0) { $channel_id = str_replace('discuss_channel_', '', $data); setup_discussion_group($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Channel selected"); }
        elseif (strpos($data, 'discuss_link_') === 0 && strpos($data, 'discuss_link_public_') !== 0) { $channel_id = str_replace('discuss_link_', '', $data); discuss_link_group_start($chat_id, $user_id, $channel_id); answerCallbackQuery($query['id'], "Send group ID"); }
        elseif ($data === 'discuss_back') { setup_discussion_start($chat_id, $user_id); answerCallbackQuery($query['id'], "Back"); }
        elseif ($data === 'discuss_close') { answerCallbackQuery($query['id'], "Closed"); }
        // Channel stats callbacks
        elseif (strpos($data, 'ch_stats_') === 0 && strpos($data, 'ch_stats_refresh_') !== 0) { $channel_id = str_replace('ch_stats_', '', $data); show_channel_stats($chat_id, $channel_id); answerCallbackQuery($query['id'], "Loading"); }
        elseif (strpos($data, 'ch_stats_refresh_') === 0) { $channel_id = str_replace('ch_stats_refresh_', '', $data); show_channel_stats($chat_id, $channel_id); answerCallbackQuery($query['id'], "Refreshed"); }
        elseif ($data === 'ch_stats_back') { channel_stats($chat_id, $user_id); answerCallbackQuery($query['id'], "Back"); }
        // Request callbacks
        elseif (strpos($data, 'req_add_') === 0) { if ($user_id == ADMIN_ID) { $parts = explode('_', $data); $req_user_id = $parts[2] ?? 0; $movie_name = urldecode($parts[3] ?? ''); sendMessage($req_user_id, "🎉 '$movie_name' ab available hai!"); $users_data = json_decode(file_get_contents(USERS_FILE), true); unset($users_data['pending_requests'][strtolower($movie_name)]); file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT)); answerCallbackQuery($query['id'], "Approved"); } else answerCallbackQuery($query['id'], "Admin only!"); }
        elseif (strpos($data, 'req_reject_') === 0) { if ($user_id == ADMIN_ID) { $parts = explode('_', $data); $req_user_id = $parts[2] ?? 0; $movie_name = urldecode($parts[3] ?? ''); sendMessage($req_user_id, "😔 '$movie_name' rejected."); $users_data = json_decode(file_get_contents(USERS_FILE), true); unset($users_data['pending_requests'][strtolower($movie_name)]); file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT)); answerCallbackQuery($query['id'], "Rejected"); } else answerCallbackQuery($query['id'], "Admin only!"); }
        else { answerCallbackQuery($query['id'], "Not found"); }
    }
}

// -------------------- WEBHOOK SETUP --------------------
if (isset($_GET['setwebhook'])) {
    $webhook_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    echo "<h1>Webhook Setup</h1><p>Result: " . htmlspecialchars($result) . "</p><p>URL: " . htmlspecialchars($webhook_url) . "</p>";
    exit;
}

// -------------------- INFO PAGE --------------------
if (!isset($update) || !$update) {
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<p>Status: ✅ Running (Ultimate Version)</p>";
    echo "<p>Movies: " . ($stats['total_movies'] ?? 0) . "</p>";
    echo "<p>Users: " . count($users_data['users'] ?? []) . "</p>";
    echo "<p>Searches: " . ($stats['total_searches'] ?? 0) . "</p>";
    echo "<p><a href='?setwebhook=1'>Set Webhook</a></p>";
}
?>
