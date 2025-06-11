<?php
include_once('utils.php');


$db = get_db();
$videos = [];
if ($db) {
    $stmt = $db->query("SELECT id, video_title, channel_title, set_date, 
                        bullshit_percent, valid_percent, niveau_percent, 
                        video_id, analysis_html, transcript ,llm_model
                        FROM video ORDER BY set_date DESC");
    $videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Analysis Dashboard</title>
    <link rel="stylesheet" href="css.css">
    <style>
        :root {
            --bg-dark: #121212;
            --bg-card: #1e1e1e;
   
            --danger: #cf6679;
            --success: #03dac6;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: var(--bg-dark);
            color: var(--text-primary);
            padding: 20px;
        }
        
        header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #333;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #333;
        }
        
        th {
            background-color: #2a2a2a;
            cursor: pointer;
            position: relative;
            user-select: none;
        }
        
        th:hover {
            background-color: #333;
        }
        
        th.sort-asc::after {
            content: '↑';
            position: absolute;
            right: 10px;
            color: var(--accent);
        }
        
        th.sort-desc::after {
            content: '↓';
            position: absolute;
            right: 10px;
            color: var(--accent);
        }
        
        tr:hover {
            background-color: #252525;
        }
        
        .btn {
            padding: 8px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.2s;
            margin-right: 5px;
        }
        
        .btn-analysis {
            background-color: darkolivegreen;
            color: white;
        }
        
        .btn-transcript {
            background-color: darkslategray;
            color: white;
        }
        
        .btn-delete {
            background-color: var(--danger);
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            background-color: var(--bg-card);
            border: 1px solid #333;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            overflow: hidden;
            resize: both;
            min-width: 300px;
            min-height: 200px;
            max-width: 90vw;
            max-height: 90vh;
            width:80vw;
            left:10vw;
        }
        
        .modal-header {
            padding: 12px 15px;
            background-color: #2a2a2a;
            cursor: move;
            border-bottom: 1px solid #333;
            display: flex;
            justify-content: space-between;
        }
        
        .modal-title {
            font-weight: 600;
        }
        
        .modal-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.2rem;
            float: right;
            width: 20px;
        }
        
        .modal-content {
            padding: 27px;
    overflow-y: scroll;
    height: 80vh;
        }
        
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            z-index: 999;
        }
        
        .rating-cell {
            font-weight: bold;
        }
        
        .rating-bullshit { color: var(--danger); }
        .rating-valid { color: var(--success); }
        .rating-niveau { color: var(--accent); }
    </style>
</head>
<body>
    <header>
        <h1>Video Analysis Dashboard</h1>
    </header>

    <table id="videoTable">

        <thead>
    <tr>
        <th data-sort="video_title">Title</th>
        <th data-sort="channel_title">Channel</th>
        <th data-sort="set_date">Date</th>
        <!-- New Model Column -->
        <th data-sort="llm_model">Model</th>
        <th data-sort="bullshit_percent">Bullshit %</th>
        <th data-sort="valid_percent">Valid %</th>
        <th data-sort="niveau_percent">Niveau %</th>
        <th>Actions</th>
    </tr>
</thead>
<tbody>
    <?php foreach ($videos as $video): ?>
    <tr data-id="<?= htmlspecialchars($video['id']) ?>">
        <td>
            <a href="https://www.youtube.com/watch?v=<?= htmlspecialchars($video['video_id']) ?>" 
               target="_blank" class="video-link">
                <?= htmlspecialchars($video['video_title']) ?>
            </a>
        </td>
        <td><?= htmlspecialchars($video['channel_title']) ?></td>
        <td><?= date('Y-m-d H:i', strtotime($video['set_date'])) ?></td>
        <!-- New Model Data Cell -->
        <td><?= htmlspecialchars($video['llm_model']) ?></td>
        <td class="rating-cell rating-bullshit"><?= htmlspecialchars($video['bullshit_percent']) ?>%</td>
        <td class="rating-cell rating-valid"><?= htmlspecialchars($video['valid_percent']) ?>%</td>
        <td class="rating-cell rating-niveau"><?= htmlspecialchars($video['niveau_percent']) ?>%</td>
        <td>
            <button class="btn btn-analysis" 
                    data-content="<?= htmlspecialchars($video['analysis_html']) ?>">Analysis</button>
            <button class="btn btn-transcript" 
                    data-content="<?= htmlspecialchars($video['transcript']) ?>">Transcript</button>
            <button class="btn btn-delete">Delete</button>
        </td>
    </tr>
    <?php endforeach; ?>
</tbody>
    </table>

    <div id="overlay" class="overlay"></div>

    <div id="modalTemplate" class="modal">
        <div class="modal-header">
            <span class="modal-title"></span>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-content"></div>
    </div>

    <script>
        // Sorting functionality
        document.querySelectorAll('th[data-sort]').forEach(header => {
            header.addEventListener('click', () => {
                const column = header.dataset.sort;
                const isAsc = header.classList.contains('sort-asc');
                const direction = isAsc ? 'desc' : 'asc';
                
                // Reset all sort indicators
                document.querySelectorAll('th').forEach(h => {
                    h.classList.remove('sort-asc', 'sort-desc');
                });
                
                // Set current sort indicator
                header.classList.add(`sort-${direction}`);
                
                sortTable(column, direction);
            });
        });

        function sortTable(column, direction) {
            const table = document.getElementById('videoTable');
            const tbody = table.querySelector('tbody');
            const rows = Array.from(tbody.querySelectorAll('tr'));
            
            rows.sort((a, b) => {
                const aVal = a.querySelector(`td:nth-child(${getIndex(column)})`).textContent;
                const bVal = b.querySelector(`td:nth-child(${getIndex(column)})`).textContent;
                
                if (column === 'set_date') {
                    return direction === 'asc' 
                        ? new Date(aVal) - new Date(bVal)
                        : new Date(bVal) - new Date(aVal);
                }
                
                if (column.includes('percent')) {
                    const aNum = parseFloat(aVal);
                    const bNum = parseFloat(bVal);
                    return direction === 'asc' ? aNum - bNum : bNum - aNum;
                }
                
                return direction === 'asc' 
                    ? aVal.localeCompare(bVal)
                    : bVal.localeCompare(aVal);
            });
            
            // Clear and re-add rows
            while (tbody.firstChild) tbody.removeChild(tbody.firstChild);
            rows.forEach(row => tbody.appendChild(row));
        }

        function getIndex(column) {
            const headers = Array.from(document.querySelectorAll('th[data-sort]'));
            return headers.findIndex(h => h.dataset.sort === column) + 1;
        }

        // Modal functionality
        let activeModal = null;
        let currentZIndex = 1000;

        document.querySelectorAll('.btn-analysis, .btn-transcript').forEach(button => {
            button.addEventListener('click', function() {
                const content = this.dataset.content;
                const title = this.classList.contains('btn-analysis') 
                    ? 'Video Analysis' 
                    : 'Transcript';
                
                createModal(title, content);
            });
        });

        function createModal(title, content) {
            const template = document.getElementById('modalTemplate');
            const modal = template.cloneNode(true);
            modal.id = '';
            modal.querySelector('.modal-title').textContent = title;
            modal.querySelector('.modal-content').innerHTML = content;
            
            document.body.appendChild(modal);
            modal.style.display = 'block';
            modal.style.zIndex = ++currentZIndex;
            
            // Position modal
            const existingModals = document.querySelectorAll('.modal:not(#modalTemplate)').length;
            modal.style.top = `${100 + existingModals * 30}px`;
            modal.style.left = `${100 + existingModals * 30}px`;
            
            // Show overlay
            const overlay = document.getElementById('overlay');
            overlay.style.display = 'block';
            overlay.style.zIndex = currentZIndex - 1;
            
            // Add event listeners
            modal.querySelector('.modal-close').addEventListener('click', () => closeModal(modal));
            makeDraggable(modal);
            
            activeModal = modal;
        }

        function closeModal(modal) {
            modal.remove();
            const modals = document.querySelectorAll('.modal:not(#modalTemplate)');
            if (modals.length === 0) {
                document.getElementById('overlay').style.display = 'none';
            }
        }

        function makeDraggable(modal) {
            const header = modal.querySelector('.modal-header');
            let pos1 = 0, pos2 = 0, pos3 = 0, pos4 = 0;
            
            header.onmousedown = dragMouseDown;
            
            function dragMouseDown(e) {
                e.preventDefault();
                modal.style.zIndex = ++currentZIndex;
                document.getElementById('overlay').style.zIndex = currentZIndex - 1;
                
                pos3 = e.clientX;
                pos4 = e.clientY;
                document.onmouseup = closeDrag;
                document.onmousemove = elementDrag;
            }
            
            function elementDrag(e) {
                e.preventDefault();
                pos1 = pos3 - e.clientX;
                pos2 = pos4 - e.clientY;
                pos3 = e.clientX;
                pos4 = e.clientY;
                
                modal.style.top = `${modal.offsetTop - pos2}px`;
                modal.style.left = `${modal.offsetLeft - pos1}px`;
            }
            
            function closeDrag() {
                document.onmouseup = null;
                document.onmousemove = null;
            }
        }

        // Delete functionality
        document.querySelectorAll('.btn-delete').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const videoId = row.dataset.id;
                
                if (confirm('Are you sure you want to delete this analysis?')) {
                    fetch(`delete_video.php?id=${videoId}`, { method: 'DELETE' })
                        .then(response => {
                            if (response.ok) {
                                row.remove();
                            } else {
                                alert('Error deleting video');
                            }
                        })
                        .catch(() => alert('Network error'));
                }
            });
        });

        // Close modal when clicking overlay
        document.getElementById('overlay').addEventListener('click', () => {
            document.querySelectorAll('.modal:not(#modalTemplate)').forEach(modal => {
                modal.remove();
            });
            document.getElementById('overlay').style.display = 'none';
        });
    </script>
</body>
</html>