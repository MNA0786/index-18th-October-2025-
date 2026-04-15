<?php
/**
 * Entertainment Tadka Bot - COMPLETE CORE VERSION
 * Features: Search + Forward + Requests + Pagination + Typing Indicator
 * CSV Format: movie_name,message_id,channel_id (LOCKED)
 * Request Destination: Admin DM
 */

// -------------------- ERROR REPORTING --------------------
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// -------------------- CONFIG --------------------
define('BOT_TOKEN', '8315381064:AAGk0FGVGmB8j5SjpBvW3rD3_kQHe_hyOWU');

// Public Channels
define('CHANNEL_1_ID', '-1003181705395');
define('CHANNEL_1_USERNAME', '@EntertainmentTadka786');
define('CHANNEL_2_ID', '-1003614546520');
define('CHANNEL_2_USERNAME', '@Entertainment_Tadka_Serial_786');
define('CHANNEL_3_ID', '-1002831605258');
define('CHANNEL_3_USERNAME', '@threater_print_movies');
define('CHANNEL_4_ID', '-1002964109368');
define('CHANNEL_4_USERNAME', '@ETBackup');

// Private Channels
define('PRIVATE_CHANNEL_1_ID', '-1003251791991');
define('PRIVATE_CHANNEL_2_ID', '-1002337293281');

// Request Group (not used anymore, request goes to Admin DM)
define('REQUEST_GROUP_ID', '-1003083386043');
define('REQUEST_GROUP_USERNAME', '@EntertainmentTadka7860');

// Default Settings
define('DEFAULT_CHANNEL_ID', CHANNEL_1_ID);
define('DEFAULT_CHANNEL_USERNAME', CHANNEL_1_USERNAME);
define('ADMIN_ID', '1080317415');
define('CSV_FILE', 'movies.csv');
define('USERS_FILE', 'users.json');
define('STATS_FILE', 'bot_stats.json');
define('ITEMS_PER_PAGE', 5);

// All channel IDs
$ALL_CHANNEL_IDS = [
    CHANNEL_1_ID, CHANNEL_2_ID, CHANNEL_3_ID, CHANNEL_4_ID,
    PRIVATE_CHANNEL_1_ID, PRIVATE_CHANNEL_2_ID
];

$ALL_CHANNELS = [
    ['id' => CHANNEL_1_ID, 'username' => CHANNEL_1_USERNAME, 'name' => 'Main Channel', 'type' => 'public'],
    ['id' => CHANNEL_2_ID, 'username' => CHANNEL_2_USERNAME, 'name' => 'Serials Channel', 'type' => 'public'],
    ['id' => CHANNEL_3_ID, 'username' => CHANNEL_3_USERNAME, 'name' => 'Theater Print', 'type' => 'public'],
    ['id' => CHANNEL_4_ID, 'username' => CHANNEL_4_USERNAME, 'name' => 'Backup Channel', 'type' => 'public'],
    ['id' => PRIVATE_CHANNEL_1_ID, 'username' => null, 'name' => 'Private Channel 1', 'type' => 'private'],
    ['id' => PRIVATE_CHANNEL_2_ID, 'username' => null, 'name' => 'Private Channel 2', 'type' => 'private']
];

// -------------------- FILE INITIALIZATION --------------------
if (!file_exists(USERS_FILE)) {
    file_put_contents(USERS_FILE, json_encode(['users' => [], 'pending_requests' => []]));
    @chmod(USERS_FILE, 0666);
}
if (!file_exists(CSV_FILE)) {
    // LOCKED: Only 3 columns
    file_put_contents(CSV_FILE, "movie_name,message_id,channel_id\n");
    @chmod(CSV_FILE, 0666);
}
if (!file_exists(STATS_FILE)) {
    file_put_contents(STATS_FILE, json_encode([
        'total_movies' => 0, 'total_users' => 0, 'total_searches' => 0, 'last_updated' => date('Y-m-d H:i:s')
    ]));
    @chmod(STATS_FILE, 0666);
}

// -------------------- GLOBAL CACHES --------------------
$movie_messages = [];
$movie_cache = [];
$waiting_users = [];

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

// ==============================
// CSV FUNCTIONS (LOCKED - 3 COLUMNS ONLY)
// ==============================
function load_and_clean_csv() {
    global $movie_messages;
    if (!file_exists(CSV_FILE)) {
        file_put_contents(CSV_FILE, "movie_name,message_id,channel_id\n");
        return [];
    }
    $data = [];
    $handle = fopen(CSV_FILE, "r");
    if ($handle !== FALSE) {
        fgetcsv($handle);
        while (($row = fgetcsv($handle)) !== FALSE) {
            if (count($row) >= 2 && (!empty(trim($row[0])))) {
                $entry = [
                    'movie_name' => trim($row[0]),
                    'message_id' => isset($row[1]) ? intval(trim($row[1])) : null,
                    'channel_id' => isset($row[2]) ? trim($row[2]) : DEFAULT_CHANNEL_ID
                ];
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
function get_all_movies_list() {
    return get_cached_movies();
}

// ==============================
// TELEGRAM API
// ==============================
function apiRequest($method, $params = []) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/" . $method;
    $options = [
        'http' => [
            'method' => 'POST',
            'content' => http_build_query($params),
            'header' => "Content-Type: application/x-www-form-urlencoded\r\n"
        ]
    ];
    $context = stream_context_create($options);
    return @file_get_contents($url, false, $context);
}
function sendMessage($chat_id, $text, $reply_markup = null, $parse_mode = null) {
    $data = ['chat_id' => $chat_id, 'text' => $text];
    if ($reply_markup) $data['reply_markup'] = json_encode($reply_markup);
    if ($parse_mode) $data['parse_mode'] = $parse_mode;
    return apiRequest('sendMessage', $data);
}
function copyMessage($chat_id, $from_chat_id, $message_id) {
    return apiRequest('copyMessage', [
        'chat_id' => $chat_id,
        'from_chat_id' => $from_chat_id,
        'message_id' => $message_id
    ]);
}
function answerCallbackQuery($callback_query_id, $text = null) {
    $data = ['callback_query_id' => $callback_query_id];
    if ($text) $data['text'] = $text;
    apiRequest('answerCallbackQuery', $data);
}
function sendChatAction($chat_id, $action = 'typing') {
    $valid_actions = ['typing', 'upload_photo', 'record_video', 'upload_video', 'record_audio', 'upload_audio', 'upload_document', 'find_location', 'record_video_note', 'upload_video_note'];
    if (!in_array($action, $valid_actions)) $action = 'typing';
    return apiRequest('sendChatAction', ['chat_id' => $chat_id, 'action' => $action]);
}

// ==============================
// DELIVERY LOGIC (Copy Message - No Forwarded Header)
// ==============================
function deliver_item_to_chat($chat_id, $item) {
    $channel_id = !empty($item['channel_id']) ? $item['channel_id'] : DEFAULT_CHANNEL_ID;
    if (!empty($item['message_id']) && is_numeric($item['message_id'])) {
        copyMessage($chat_id, $channel_id, $item['message_id']);
        return true;
    }
    return false;
}
function append_movie($movie_name, $message_id, $channel_id = null) {
    if (empty(trim($movie_name))) return;
    if ($channel_id === null) $channel_id = DEFAULT_CHANNEL_ID;
    $entry = [$movie_name, $message_id, $channel_id];
    $handle = fopen(CSV_FILE, "a");
    fputcsv($handle, $entry);
    fclose($handle);
    global $movie_messages, $movie_cache, $waiting_users;
    $movie = strtolower(trim($movie_name));
    $item = [
        'movie_name' => $movie_name,
        'message_id' => $message_id,
        'channel_id' => $channel_id
    ];
    if (!isset($movie_messages[$movie])) $movie_messages[$movie] = [];
    $movie_messages[$movie][] = $item;
    $movie_cache = [];
    foreach ($waiting_users as $query => $users) {
        if (strpos($movie, $query) !== false) {
            foreach ($users as $user_data) {
                list($user_chat_id, $user_id) = $user_data;
                deliver_item_to_chat($user_chat_id, $item);
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
    return [
        'total' => $total,
        'total_pages' => $total_pages,
        'page' => $page,
        'slice' => array_slice($all, $start, ITEMS_PER_PAGE)
    ];
}
function forward_page_movies($chat_id, array $page_movies) {
    foreach ($page_movies as $movie) {
        deliver_item_to_chat($chat_id, $movie);
        usleep(300000);
    }
}
function build_totalupload_keyboard(int $page, int $total_pages): array {
    $kb = ['inline_keyboard' => []];
    $nav_row = [];
    if ($page > 1) $nav_row[] = ['text' => '⬅️ Previous', 'callback_data' => 'tu_prev_' . ($page - 1)];
    $nav_row[] = ['text' => "📄 $page/$total_pages", 'callback_data' => 'current_page'];
    if ($page < $total_pages) $nav_row[] = ['text' => 'Next ➡️', 'callback_data' => 'tu_next_' . ($page + 1)];
    if (!empty($nav_row)) $kb['inline_keyboard'][] = $nav_row;
    $kb['inline_keyboard'][] = [
        ['text' => '🎬 Send This Page', 'callback_data' => 'tu_view_' . $page],
        ['text' => '🛑 Stop', 'callback_data' => 'tu_stop']
    ];
    return $kb;
}
function totalupload_controller($chat_id, $page = 1) {
    $all = get_all_movies_list();
    if (empty($all)) {
        sendMessage($chat_id, "📭 Koi movies nahi mili!");
        return;
    }
    $pg = paginate_movies($all, (int)$page);
    forward_page_movies($chat_id, $pg['slice']);
    $title = "🎬 <b>Total Uploads</b>\n\n📊 Page {$pg['page']}/{$pg['total_pages']}\n📊 Total Movies: {$pg['total']}";
    $kb = build_totalupload_keyboard($pg['page'], $pg['total_pages']);
    sendMessage($chat_id, $title, $kb, 'HTML');
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
        else {
            similar_text($movie, $query_lower, $similarity);
            if ($similarity > 60) $score = $similarity;
        }
        if ($score > 0) $results[$movie] = ['score' => $score, 'count' => count($entries)];
    }
    uasort($results, function($a, $b) {
        return $b['score'] - $a['score'];
    });
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
    $responses = [
        'hindi' => [
            'not_found' => "😔 Yeh movie abhi available nahi hai!\n\n📝 Aap ise request kar sakte hain: @EntertainmentTadka0786",
            'searching' => "🔍 Dhoondh raha hoon..."
        ],
        'english' => [
            'not_found' => "😔 This movie isn't available yet!\n\n📝 You can request it here: @EntertainmentTadka0786",
            'searching' => "🔍 Searching..."
        ]
    ];
    sendMessage($chat_id, $responses[$language][$message_type]);
}
function advanced_search($chat_id, $query, $user_id = null) {
    global $movie_messages, $waiting_users;
    $q = strtolower(trim($query));
    if (strlen($q) < 2) {
        sendMessage($chat_id, "❌ Please enter at least 2 characters");
        return;
    }
    $found = smart_search($q);
    if (!empty($found)) {
        $msg = "🔍 Found " . count($found) . " movies for '$query':\n\n";
        $i = 1;
        foreach ($found as $movie => $data) {
            $msg .= "$i. $movie\n";
            $i++;
            if ($i > 10) break;
        }
        sendMessage($chat_id, $msg);
        $keyboard = ['inline_keyboard' => []];
        foreach (array_slice(array_keys($found), 0, 5) as $movie) {
            $keyboard['inline_keyboard'][] = [['text' => "🎬 " . ucwords($movie), 'callback_data' => $movie]];
        }
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
// REQUEST FUNCTIONS (Sends to Admin DM)
// ==============================
function request_movie($chat_id, $user_id, $movie_name, $user_firstname) {
    $msg = "🎬 <b>New Movie Request!</b>\n\n";
    $msg .= "👤 User: $user_firstname\n";
    $msg .= "🆔 User ID: <code>$user_id</code>\n";
    $msg .= "🍿 Movie: <code>" . htmlspecialchars($movie_name) . "</code>\n";
    $msg .= "📅 Date: " . date('d-m-Y H:i:s');
    $keyboard = [
        'inline_keyboard' => [[
            ['text' => '✅ Approve', 'callback_data' => 'req_add_' . $user_id . '_' . urlencode($movie_name)],
            ['text' => '❌ Reject', 'callback_data' => 'req_reject_' . $user_id . '_' . urlencode($movie_name)]
        ]]
    ];
    // Request goes to Admin DM, not group
    sendMessage(ADMIN_ID, $msg, $keyboard, 'HTML');
    sendMessage($chat_id, "✅ Request '$movie_name' submit ho gayi!\nAdmin jald add karega.");
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    if (!isset($users_data['pending_requests'][strtolower($movie_name)])) {
        $users_data['pending_requests'][strtolower($movie_name)] = [];
    }
    $users_data['pending_requests'][strtolower($movie_name)][] = [
        'user_id' => $user_id, 'chat_id' => $chat_id, 'movie' => $movie_name, 'date' => date('Y-m-d H:i:s')
    ];
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
}
function get_user_requests($chat_id, $user_id) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $user_requests = [];
    foreach ($users_data['pending_requests'] ?? [] as $movie => $requests) {
        foreach ($requests as $req) {
            if ($req['user_id'] == $user_id) $user_requests[] = $movie;
        }
    }
    if (empty($user_requests)) {
        sendMessage($chat_id, "📭 No pending requests!");
        return;
    }
    $msg = "📊 Your Pending Requests:\n\n";
    foreach ($user_requests as $i => $req) {
        $msg .= ($i+1) . ". " . htmlspecialchars($req) . "\n";
    }
    sendMessage($chat_id, $msg, null, 'HTML');
}
function pending_requests($chat_id) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $pending = $users_data['pending_requests'] ?? [];
    if (empty($pending)) {
        sendMessage($chat_id, "📭 No pending requests!");
        return;
    }
    $msg = "📊 Pending Requests:\n\n";
    $i = 1;
    foreach ($pending as $movie => $requests) {
        $msg .= "$i. " . htmlspecialchars($movie) . " - " . count($requests) . " users\n";
        $i++;
    }
    sendMessage($chat_id, $msg, null, 'HTML');
}
function bulk_approve_requests($chat_id, $count = 10) {
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    $pending = &$users_data['pending_requests'];
    if (empty($pending)) {
        sendMessage($chat_id, "📭 No pending requests!");
        return;
    }
    $approved = 0;
    foreach ($pending as $movie => $requests) {
        if ($approved >= $count) break;
        foreach ($requests as $req) {
            sendMessage($req['chat_id'], "🎉 '$movie' ab available hai! Search karo.");
        }
        unset($pending[$movie]);
        $approved++;
    }
    file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
    sendMessage($chat_id, "✅ Bulk approved $approved movies!");
}

// ==============================
// MAIN WEBHOOK HANDLER
// ==============================
$update = json_decode(file_get_contents('php://input'), true);
if ($update) {
    get_cached_movies();
    
    // -------------------- CHANNEL POST HANDLING --------------------
    if (isset($update['channel_post'])) {
        $message = $update['channel_post'];
        $message_id = $message['message_id'];
        $chat_id = $message['chat']['id'];
        if (in_array($chat_id, $ALL_CHANNEL_IDS)) {
            $text = $message['caption'] ?? $message['text'] ?? $message['document']['file_name'] ?? 'Media';
            if (!empty(trim($text))) {
                append_movie($text, $message_id, $chat_id);
            }
        }
    }
    
    // -------------------- MESSAGE HANDLING --------------------
    if (isset($update['message'])) {
        $message = $update['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $message['from']['id'];
        $text = $message['text'] ?? '';
        
        // Save user
        $users_data = json_decode(file_get_contents(USERS_FILE), true);
        if (!isset($users_data['users'][$user_id])) {
            $users_data['users'][$user_id] = [
                'first_name' => $message['from']['first_name'] ?? '',
                'username' => $message['from']['username'] ?? '',
                'joined' => date('Y-m-d H:i:s')
            ];
            file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
            update_stats('total_users', 1);
        }
        
        // -------------------- TYPING INDICATOR --------------------
        sendChatAction($chat_id, 'typing');
        
        // -------------------- COMMANDS --------------------
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = $parts[0];
            
            switch ($command) {
                case '/start':
                    $welcome = "🎬 Welcome to Entertainment Tadka!\n\n";
                    $welcome .= "📢 How to use this bot:\n";
                    $welcome .= "• Simply type any movie name\n";
                    $welcome .= "• Partial names also work\n\n";
                    $welcome .= "🔍 Examples:\n";
                    $welcome .= "• Mandala Murders 2025\n";
                    $welcome .= "• Zebra 2024\n";
                    $welcome .= "• Now You See Me\n";
                    $welcome .= "• Squid Game\n";
                    $welcome .= "• Show Time (2024)\n";
                    $welcome .= "• Taskaree S01 (2025)\n\n";
                    $welcome .= "❌ Don't type:\n";
                    $welcome .= "• Technical questions\n";
                    $welcome .= "• Player instructions\n";
                    $welcome .= "• Non-movie queries\n\n";
                    $welcome .= "📢 Join our channels:\n";
                    $welcome .= "🍿 Main: " . CHANNEL_1_USERNAME . "\n";
                    $welcome .= "📺 Serial: " . CHANNEL_2_USERNAME . "\n";
                    $welcome .= "🎭 Theater: " . CHANNEL_3_USERNAME . "\n";
                    $welcome .= "🔒 Backup: " . CHANNEL_4_USERNAME . "\n\n";
                    $welcome .= "📥 Request movies: @EntertainmentTadka0786\n";
                    $welcome .= "💬 Need help? Use /help";
                    
                    $keyboard = [
                        'inline_keyboard' => [
                            [
                                ['text' => '🍿 Main Channel', 'url' => 'https://t.me/' . ltrim(CHANNEL_1_USERNAME, '@')],
                                ['text' => '📺 Serial Channel', 'url' => 'https://t.me/' . ltrim(CHANNEL_2_USERNAME, '@')],
                                ['text' => '🎭 Theater Channel', 'url' => 'https://t.me/' . ltrim(CHANNEL_3_USERNAME, '@')]
                            ],
                            [
                                ['text' => '📥 Request Movies', 'url' => 'https://t.me/EntertainmentTadka0786'],
                                ['text' => '🔒 Backup Channel', 'url' => 'https://t.me/' . ltrim(CHANNEL_4_USERNAME, '@')],
                                ['text' => '🤖 Bot', 'url' => 'https://t.me/EntertainmentTadkaBot']
                            ],
                            [
                                ['text' => '📁 All Movies', 'callback_data' => 'user_totaluploads'],
                                ['text' => '📝 My Requests', 'callback_data' => 'user_myrequests'],
                                ['text' => '❓ Help', 'callback_data' => 'user_help']
                            ]
                        ]
                    ];
                    
                    sendMessage($chat_id, $welcome, $keyboard, 'HTML');
                    break;
                    
                case '/help':
                    $help = "📁 Browse Commands:\n";
                    $help .= "• /totaluploads - All movies\n\n";
                    $help .= "📝 Request Commands:\n";
                    $help .= "• /request movie - Request movie\n";
                    $help .= "• /myrequests - Your pending requests\n\n";
                    $help .= "👑 Admin Commands:\n";
                    $help .= "• /pending_request - Check pending requests\n";
                    $help .= "• /bulk_approve [count] - Bulk approve requests\n\n";
                    $help .= "💬 Help: @EntertainmentTadka0786";
                    sendMessage($chat_id, $help, null, 'HTML');
                    break;
                    
                case '/totalupload':
                case '/totaluploads':
                    totalupload_controller($chat_id, 1);
                    break;
                    
                case '/request':
                    if (isset($parts[1]) && !empty(trim($parts[1]))) {
                        $req_movie = trim(implode(' ', array_slice($parts, 1)));
                        $user_firstname = $message['from']['first_name'] ?? 'User';
                        request_movie($chat_id, $user_id, $req_movie, $user_firstname);
                    } else {
                        sendMessage($chat_id, "❌ Usage: /request <movie name>\n\nExample: /request Pushpa 2", null, 'HTML');
                    }
                    break;
                    
                case '/myrequests':
                    get_user_requests($chat_id, $user_id);
                    break;
                    
                case '/pending_request':
                    if ($user_id == ADMIN_ID) {
                        pending_requests($chat_id);
                    } else {
                        sendMessage($chat_id, "❌ Only admin can use this command!");
                    }
                    break;
                    
                case '/bulk_approve':
                    if ($user_id == ADMIN_ID) {
                        $count = isset($parts[1]) ? intval($parts[1]) : 10;
                        bulk_approve_requests($chat_id, $count);
                    } else {
                        sendMessage($chat_id, "❌ Only admin can use this command!");
                    }
                    break;
                    
                default:
                    // Unknown command
                    break;
            }
        } elseif (!empty(trim($text))) {
            // Text search
            $lang = detect_language($text);
            send_multilingual_response($chat_id, 'searching', $lang);
            advanced_search($chat_id, $text, $user_id);
        }
    }
    
    // -------------------- CALLBACK QUERY HANDLING --------------------
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $message = $query['message'];
        $chat_id = $message['chat']['id'];
        $user_id = $query['from']['id'];
        $data = $query['data'];
        
        global $movie_messages;
        $movie_lower = strtolower($data);
        
        // Movie selection callback
        if (isset($movie_messages[$movie_lower])) {
            $entries = $movie_messages[$movie_lower];
            foreach ($entries as $entry) {
                deliver_item_to_chat($chat_id, $entry);
                usleep(200000);
            }
            sendMessage($chat_id, "✅ Movie forwarded!\n\n📢 Join our channels!");
            answerCallbackQuery($query['id'], "Sent!");
        }
        // User buttons
        elseif ($data === 'user_totaluploads') {
            totalupload_controller($chat_id, 1);
            answerCallbackQuery($query['id'], "Loading all movies...");
        }
        elseif ($data === 'user_myrequests') {
            get_user_requests($chat_id, $user_id);
            answerCallbackQuery($query['id'], "Your pending requests");
        }
        elseif ($data === 'user_help') {
            sendMessage($chat_id, "Use /help for commands", null, 'HTML');
            answerCallbackQuery($query['id'], "Help menu");
        }
        // Pagination callbacks
        elseif (strpos($data, 'tu_prev_') === 0) {
            $page = (int)str_replace('tu_prev_', '', $data);
            totalupload_controller($chat_id, $page);
            answerCallbackQuery($query['id'], "Page $page");
        }
        elseif (strpos($data, 'tu_next_') === 0) {
            $page = (int)str_replace('tu_next_', '', $data);
            totalupload_controller($chat_id, $page);
            answerCallbackQuery($query['id'], "Page $page");
        }
        elseif (strpos($data, 'tu_view_') === 0) {
            $page = (int)str_replace('tu_view_', '', $data);
            $all = get_all_movies_list();
            $pg = paginate_movies($all, $page);
            forward_page_movies($chat_id, $pg['slice']);
            answerCallbackQuery($query['id'], "Re-sent current page movies");
        }
        elseif ($data === 'tu_stop') {
            sendMessage($chat_id, "✅ Pagination stopped. Type /totalupload to start again.");
            answerCallbackQuery($query['id'], "Stopped");
        }
        elseif ($data === 'current_page') {
            answerCallbackQuery($query['id'], "You're on this page");
        }
        // Request approval callbacks
        elseif (strpos($data, 'req_add_') === 0) {
            if ($user_id == ADMIN_ID) {
                $parts = explode('_', $data);
                $req_user_id = $parts[2] ?? 0;
                $movie_name = urldecode($parts[3] ?? '');
                sendMessage($req_user_id, "🎉 '<b>" . htmlspecialchars($movie_name) . "</b>' ab channel me add ho gayi!\n\n📢 Check karo: " . DEFAULT_CHANNEL_USERNAME, null, 'HTML');
                $users_data = json_decode(file_get_contents(USERS_FILE), true);
                unset($users_data['pending_requests'][strtolower($movie_name)]);
                file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
                answerCallbackQuery($query['id'], "✅ Request approved");
            } else {
                answerCallbackQuery($query['id'], "❌ Admin only!");
            }
        }
        elseif (strpos($data, 'req_reject_') === 0) {
            if ($user_id == ADMIN_ID) {
                $parts = explode('_', $data);
                $req_user_id = $parts[2] ?? 0;
                $movie_name = urldecode($parts[3] ?? '');
                sendMessage($req_user_id, "😔 Sorry, '<b>" . htmlspecialchars($movie_name) . "</b>' currently available nahi hai.", null, 'HTML');
                $users_data = json_decode(file_get_contents(USERS_FILE), true);
                unset($users_data['pending_requests'][strtolower($movie_name)]);
                file_put_contents(USERS_FILE, json_encode($users_data, JSON_PRETTY_PRINT));
                answerCallbackQuery($query['id'], "❌ Request rejected");
            } else {
                answerCallbackQuery($query['id'], "❌ Admin only!");
            }
        }
        else {
            sendMessage($chat_id, "❌ Movie not found: " . $data);
            answerCallbackQuery($query['id'], "❌ Movie not available");
        }
    }
}

// -------------------- WEBHOOK SETUP PAGE --------------------
if (isset($_GET['setwebhook'])) {
    $webhook_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    $result = apiRequest('setWebhook', ['url' => $webhook_url]);
    echo "<h1>🔧 Webhook Setup</h1>";
    echo "<p>Result: " . htmlspecialchars($result) . "</p>";
    echo "<p>Webhook URL: " . htmlspecialchars($webhook_url) . "</p>";
    
    $bot_info = json_decode(apiRequest('getMe'), true);
    if ($bot_info && isset($bot_info['ok']) && $bot_info['ok']) {
        echo "<h2>🤖 Bot Info</h2>";
        echo "<p>Name: " . htmlspecialchars($bot_info['result']['first_name']) . "</p>";
        echo "<p>Username: @" . htmlspecialchars($bot_info['result']['username']) . "</p>";
    }
    exit;
}

// -------------------- INFO PAGE (NO UPDATE) --------------------
if (!isset($update) || !$update) {
    $stats = get_stats();
    $users_data = json_decode(file_get_contents(USERS_FILE), true);
    
    echo "<h1>🎬 Entertainment Tadka Bot</h1>";
    echo "<p><strong>Status:</strong> ✅ Running (Core Version)</p>";
    echo "<p><strong>Total Movies:</strong> " . ($stats['total_movies'] ?? 0) . "</p>";
    echo "<p><strong>Total Users:</strong> " . count($users_data['users'] ?? []) . "</p>";
    echo "<p><strong>Total Searches:</strong> " . ($stats['total_searches'] ?? 0) . "</p>";
    echo "<p><strong>Pending Requests:</strong> " . count($users_data['pending_requests'] ?? []) . "</p>";
    
    echo "<h3>📺 Connected Channels</h3>";
    echo "<ul>";
    foreach ($ALL_CHANNELS as $ch) {
        if ($ch['type'] == 'public') {
            echo "<li>🔓 " . $ch['username'] . " - " . $ch['name'] . "</li>";
        } else {
            echo "<li>🔒 " . $ch['name'] . " (Private)</li>";
        }
    }
    echo "</ul>";
    
    echo "<h3>🚀 Quick Setup</h3>";
    echo "<p><a href='?setwebhook=1'>Set Webhook Now</a></p>";
    
    echo "<h3>📋 Available Commands</h3>";
    echo "<ul>";
    echo "<li><code>/start</code> - Welcome message</li>";
    echo "<li><code>/help</code> - Help message</li>";
    echo "<li><code>/totaluploads</code> - Browse all movies</li>";
    echo "<li><code>/request &lt;movie&gt;</code> - Request a movie</li>";
    echo "<li><code>/myrequests</code> - Your pending requests</li>";
    echo "<li><code>/pending_request</code> - Check pending requests (Admin)</li>";
    echo "<li><code>/bulk_approve [count]</code> - Bulk approve requests (Admin)</li>";
    echo "</ul>";
}
?>
