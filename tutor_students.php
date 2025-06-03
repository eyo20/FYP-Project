<?php
session_start();
require_once "db_connection.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'tutor') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Automatically update sessions to 'completed' if 12 hours past end_datetime
$current_time = date('Y-m-d H:i:s');
$threshold_time = date('Y-m-d H:i:s', strtotime('-12 hours'));
$stmt = $conn->prepare("UPDATE session SET status = 'completed' WHERE status = 'scheduled' AND end_datetime <= ?");
if ($stmt) {
    $stmt->bind_param("s", $threshold_time);
    $stmt->execute();
    $stmt->close();
}

// Get current sessions (status = 'scheduled')
$current_sessions_query = "SELECT s.session_id, s.start_datetime, s.end_datetime, s.status,
                          u.user_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image,
                          c.course_name, c.id as course_id, l.location_name
                          FROM session s
                          JOIN user u ON s.student_id = u.user_id
                          JOIN course c ON s.course_id = c.id
                          LEFT JOIN location l ON s.location_id = l.location_id
                          WHERE s.tutor_id = ? AND s.status = 'scheduled'
                          ORDER BY s.start_datetime ASC";
$stmt = $conn->prepare($current_sessions_query);
if (!$stmt) {
    die("ERROR: Failed to prepare SQL statement: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_sessions_result = $stmt->get_result();
$current_sessions = [];
while ($row = $current_sessions_result->fetch_assoc()) {
    $current_sessions[] = $row;
}
$stmt->close();

// Get past sessions (status = 'completed' or 'cancelled')
$past_sessions_query = "SELECT s.session_id, s.start_datetime, s.end_datetime, s.status,
                       u.first_name, u.last_name, u.email, c.course_name, l.location_name
                       FROM session s
                       JOIN user u ON s.student_id = u.user_id
                       JOIN course c ON s.course_id = c.id
                       LEFT JOIN location l ON s.location_id = l.location_id
                       WHERE s.tutor_id = ? AND s.status IN ('completed', 'cancelled')
                       ORDER BY s.start_datetime DESC";
$stmt = $conn->prepare($past_sessions_query);
if (!$stmt) {
    die("ERROR: Failed to prepare SQL query: " . htmlspecialchars($conn->error));
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$past_sessions_result = $stmt->get_result();
$past_sessions = [];
while ($row = $past_sessions_result->fetch_assoc()) {
    $past_sessions[] = $row;
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Peer Tutoring Platform</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --primary-dark: #0056b3;
            --gray: #6c757d;
            --dark-gray: #343a40;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, 'Open Sans', 'Helvetica Neue', sans-serif;
        }

        body {
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }

        main {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        h1 {
            text-align: center;
            margin-bottom: 2rem;
            color: var(--dark-gray);
        }

        .tabs {
            display: flex;
            border-bottom: 2px solid var(--gray);
            margin-bottom: 2rem;
        }

        .tab {
            flex: 1;
            text-align: center;
            padding: 1rem;
            cursor: pointer;
            background-color: var(--white);
            transition: background-color 0.3s;
        }

        .tab.active {
            background-color: var(--primary);
            color: var(--white);
        }

        .tab:hover {
            background-color: var(--primary-dark);
            color: var(--white);
        }

        .session-list {
            display: grid;
            gap: 1.5rem;
        }

        .session-card {
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .session-details {
            flex: 1;
        }

        .session-details div {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .session-details i {
            color: var(--primary);
        }

        .session-actions {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: background-color 0.3s;
        }

        .btn-primary {
            background-color: var(--primary);
            color: var(--white);
            border: none;
        }

        .btn-primary:hover {
            background-color: var(--primary-dark);
        }

        .btn-outline {
            border: 1px solid var(--primary);
            color: var(--primary);
            background-color: transparent;
        }

        .btn-outline:hover {
            background-color: var(--primary);
            color: var(--white);
        }

        .empty-state {
            text-align: center;
            padding: 2rem;
            background-color: var(--white);
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: var(--white);
            padding: 2rem;
            border-radius: 8px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-content h2 {
            margin-bottom: 1rem;
        }

        .modal-content table {
            width: 100%;
            border-collapse: collapse;
        }

        .modal-content th, .modal-content td {
            padding: 0.75rem;
            border-bottom: 1px solid var(--gray);
        }

        .modal-content th {
            background-color: var(--light-gray);
        }

        .close-btn {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include 'tut_head.php'; ?>
    <main>
        <h1>My Students</h1>
        <div class="tabs">
            <div class="tab active" data-tab="current">Current Sessions</div>
            <div class="tab" data-tab="past">Past Sessions</div>
        </div>

        <!-- Current Sessions -->
        <div class="session-list" id="current-sessions">
            <?php if (count($current_sessions) > 0): ?>
                <?php foreach ($current_sessions as $session): ?>
                    <div class="session-card">
                        <div class="session-details">
                            <div class="session-time">
                                <i class="far fa-calendar-alt"></i>
                                <span>
                                    <?php
                                    $start = new DateTime($session['start_datetime']);
                                    $end = new DateTime($session['end_datetime']);
                                    echo $start->format('M j, Y') . ' | ' . $start->format('g:i A') . ' - ' . $end->format('g:i A');
                                    ?>
                                </span>
                            </div>
                            <div class="session-student">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></span>
                            </div>
                            <div class="session-course">
                                <i class="fas fa-book"></i>
                                <span><?php echo htmlspecialchars($session['course_name']); ?></span>
                            </div>
                            <div class="session-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($session['location_name'] ?: 'Not specified'); ?></span>
                            </div>
                        </div>
                        <div class="session-actions">
                            <a href="tutor_messages.php?student_id=<?php echo $session['user_id']; ?>" class="btn btn-outline">Message</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Current Sessions</h3>
                    <p>You don't have any upcoming sessions with students.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Past Sessions -->
        <div class="session-list" id="past-sessions" style="display: none;">
            <?php if (count($past_sessions) > 0): ?>
                <?php foreach ($past_sessions as $session): ?>
                    <div class="session-card">
                        <div class="session-details">
                            <div class="session-time">
                                <i class="far fa-calendar-alt"></i>
                                <span>
                                    <?php
                                    $start = new DateTime($session['start_datetime']);
                                    $end = new DateTime($session['end_datetime']);
                                    echo $start->format('M j, Y') . ' | ' . $start->format('g:i A') . ' - ' . $end->format('g:i A');
                                    ?>
                                </span>
                            </div>
                            <div class="session-student">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($session['first_name'] . ' ' . $session['last_name']); ?></span>
                            </div>
                            <div class="session-course">
                                <i class="fas fa-book"></i>
                                <span><?php echo htmlspecialchars($session['course_name']); ?></span>
                            </div>
                            <div class="session-location">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($session['location_name'] ?: 'Not specified'); ?></span>
                            </div>
                            <div class="session-status">
                                <i class="fas fa-info-circle"></i>
                                <span><?php echo ucfirst($session['status']); ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <h3>No Past Sessions</h3>
                    <p>You don't have any past sessions with students.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal for Session History -->
        <div class="modal" id="session-history-modal">
            <div class="modal-content">
                <span class="close-btn">×</span>
                <h2>Session History</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Student</th>
                            <th>Course</th>
                            <th>Location</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="session-history-table"></tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const tabs = document.querySelectorAll('.tab');
            const sessionLists = document.querySelectorAll('.session-list');
            const modal = document.getElementById('session-history-modal');
            const closeBtn = document.querySelector('.close-btn');
            const historyTable = document.getElementById('session-history-table');

            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    tab.classList.add('active');

                    sessionLists.forEach(list => list.style.display = 'none');
                    document.getElementById(`${tab.dataset.tab}-sessions`).style.display = 'grid';

                    if (tab.dataset.tab === 'past') {
                        modal.style.display = 'flex';
                        loadSessionHistory();
                    }
                });
            });

            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            window.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });

            function loadSessionHistory() {
                const sessions = <?php echo json_encode($past_sessions); ?>;
                historyTable.innerHTML = '';
                sessions.forEach(session => {
                    const start = new Date(session.start_datetime);
                    const end = new Date(session.end_datetime);
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${start.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td>${start.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })} - ${end.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit' })}</td>
                        <td>${session.first_name} ${session.last_name}</td>
                        <td>${session.course_name}</td>
                        <td>${session.location_name || 'Not specified'}</td>
                        <td>${session.status.charAt(0).toUpperCase() + session.status.slice(1)}</td>
                    `;
                    historyTable.appendChild(row);
                });
            }
        });
    </script>
</body>
</html>