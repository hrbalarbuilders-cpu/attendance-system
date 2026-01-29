// Attendance Details Modal Script (Modern Premium Version)
(function () {
    let attendanceModal = null;
    let currentEmpId = null;
    let currentDate = null;
    let locationMapModal = null;
    let dedicatedMap = null;
    let satelliteLayer = null;
    let roadLayer = null;

    function initModals() {
        const detailModalEl = document.getElementById('attendanceDetailsModal');
        if (detailModalEl && !attendanceModal) {
            attendanceModal = new bootstrap.Modal(detailModalEl);
        }

        const mapModalEl = document.getElementById('attendanceLocationMapModal');
        if (mapModalEl && !locationMapModal) {
            locationMapModal = new bootstrap.Modal(mapModalEl);

            // Re-invalidate map size when modal is shown
            mapModalEl.addEventListener('shown.bs.modal', function () {
                if (dedicatedMap) {
                    dedicatedMap.invalidateSize();
                }
            });
        }

        const addActivityBtn = document.getElementById('addActivityBtn');
        if (addActivityBtn) {
            addActivityBtn.addEventListener('click', function () {
                if (typeof showMarkAttendanceModal === 'function') {
                    // Hide current modal if needed, or just show the other one over it
                    // bootstrap.Modal.getInstance(document.getElementById('attendanceDetailsModal')).hide();
                    showMarkAttendanceModal(currentEmpId, currentDate);
                }
            });
        }
    }

    function populateModal(data) {
        const logs = data.logs || [];
        const activities = [];

        logs.forEach(log => {
            activities.push({
                id: log.id,
                type: log.type,
                time: log.time,
                working_from: log.working_from || '',
                reason: log.reason || 'normal',
                lat: log.lat,
                lng: log.lng
            });
        });

        activities.sort((a, b) => new Date(a.time) - new Date(b.time));

        // Employee Info
        const modalEmpAvatar = document.getElementById('modalEmpAvatar');
        if (modalEmpAvatar && data.employee) {
            modalEmpAvatar.textContent = (data.employee.name || 'E').charAt(0).toUpperCase();
        }

        // Show clock in if available
        const firstIn = activities.find(a => a.type === 'in');
        const clockInBox = document.getElementById('clockInBox');
        const clockInTime = document.getElementById('clockInTime');
        const clockInGreeting = document.getElementById('clockInGreeting');

        if (firstIn && clockInBox && clockInTime && clockInGreeting) {
            const clockInTimeObj = new Date(firstIn.time);
            const timeStr = clockInTimeObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            const hour = clockInTimeObj.getHours();
            let greeting = 'Good morning! 👋';
            if (hour >= 12 && hour < 17) greeting = 'Good afternoon! ☀️';
            else if (hour >= 17) greeting = 'Good evening! 🌙';

            clockInTime.textContent = timeStr;
            clockInGreeting.textContent = greeting;
            clockInBox.style.display = 'block';
        } else {
            if (clockInBox) clockInBox.style.display = 'none';
        }

        // Show clock out if available
        const lastOut = activities.filter(a => a.type === 'out').pop();
        const clockOutBox = document.getElementById('clockOutBox');
        const clockOutTime = document.getElementById('clockOutTime');
        const clockOutGreeting = document.getElementById('clockOutGreeting');

        if (lastOut && clockOutBox && clockOutTime && clockOutGreeting) {
            const clockOutTimeObj = new Date(lastOut.time);
            const timeStr = clockOutTimeObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
            const hour = clockOutTimeObj.getHours();
            let greeting = 'Have a great day! 👋';
            if (hour >= 12 && hour < 17) greeting = 'Have a wonderful afternoon! ☀️';
            else if (hour >= 17) greeting = 'Have a great evening! 🌙';

            clockOutTime.textContent = timeStr;
            clockOutGreeting.textContent = greeting;
            clockOutBox.style.display = 'block';
        } else {
            if (clockOutBox) clockOutBox.style.display = 'none';
        }

        // Calculate total work time with breaks (Premium logic preserved)
        const inLogs = activities.filter(a => a.type === 'in');
        const outLogs = activities.filter(a => a.type === 'out');
        const totalWorkBox = document.getElementById('totalWorkBox');
        const totalWorkTime = document.getElementById('totalWorkTime');
        const grossWorkTime = document.getElementById('grossWorkTime');
        const workProgressCircle = document.getElementById('workProgressCircle');
        const breakProgressCircle = document.getElementById('breakProgressCircle');
        const lateProgressCircle = document.getElementById('lateProgressCircle');

        if (inLogs.length > 0 && outLogs.length > 0 && totalWorkBox && totalWorkTime && workProgressCircle) {
            const firstInTime = new Date(inLogs[0].time);
            const lastOutTime = new Date(outLogs[outLogs.length - 1].time);

            const grossMs = lastOutTime - firstInTime;
            const grossHours = Math.floor(grossMs / (1000 * 60 * 60));
            const grossMinutes = Math.floor((grossMs % (1000 * 60 * 60)) / (1000 * 60));

            let totalBreakMs = 0;
            let firstBreakOffsetMs = null;
            for (let i = 0; i < activities.length - 1; i++) {
                const current = activities[i];
                const next = activities[i + 1];
                if (current.type === 'out' && (current.reason === 'lunch' || current.reason === 'tea') && next && next.type === 'in') {
                    const breakStart = new Date(current.time);
                    const breakEnd = new Date(next.time);
                    totalBreakMs += (breakEnd - breakStart);
                    if (firstBreakOffsetMs === null) firstBreakOffsetMs = breakStart - firstInTime;
                }
            }

            let lateMs = 0;
            if (data.shift && data.shift.start_time_raw) {
                const shiftStart = new Date(data.date + 'T' + data.shift.start_time_raw);
                if (firstInTime > shiftStart) lateMs = firstInTime - shiftStart;
            }

            const effectiveMs = grossMs - totalBreakMs;
            const effHours = Math.floor(effectiveMs / (1000 * 60 * 60));
            const effMins = Math.floor((effectiveMs % (1000 * 60 * 60)) / (1000 * 60));

            totalWorkTime.textContent = effHours + 'hr ' + effMins + 'min';
            if (totalBreakMs > 0 && grossWorkTime) {
                grossWorkTime.textContent = 'Gross: ' + grossHours + 'hr ' + grossMinutes + 'min';
                grossWorkTime.style.display = 'block';
            } else if (grossWorkTime) {
                grossWorkTime.style.display = 'none';
            }

            // Calculate progress percentage (based on shift duration, fallback to 9 hours)
            let shiftDurationMs = 9 * 60 * 60 * 1000;
            if (data.shift && data.shift.start_time_raw && data.shift.end_time_raw) {
                const sStart = new Date(data.date + 'T' + data.shift.start_time_raw);
                const sEnd = new Date(data.date + 'T' + data.shift.end_time_raw);
                if (sEnd > sStart) shiftDurationMs = sEnd - sStart;
                else if (sEnd < sStart) shiftDurationMs = (sEnd.getTime() + 24 * 60 * 60 * 1000) - sStart.getTime(); // Overnight
            }

            const percentage = Math.min(effectiveMs / shiftDurationMs, 1);
            const circumference = 427;
            const workOffset = circumference - (percentage * circumference);

            workProgressCircle.style.strokeDasharray = circumference;
            workProgressCircle.style.strokeDashoffset = grossMs > 0 ? workOffset : circumference;

            // Optional: Map other segments if needed, but keeping it clean for now
            if (breakProgressCircle) breakProgressCircle.style.display = 'none';
            if (lateProgressCircle) lateProgressCircle.style.display = 'none';

            totalWorkBox.style.display = 'block';
        } else {
            if (totalWorkBox) totalWorkBox.style.display = 'none';
        }

        // --- TIMELINE OVERHAUL ---
        const activityTimeline = document.getElementById('activityTimeline');
        const activityCount = document.getElementById('activityCount');
        let timelineHTML = '';

        if (activities.length > 0) {
            if (activityCount) activityCount.textContent = activities.length + ' activities today';

            activities.forEach((activity, idx) => {
                const activityTime = new Date(activity.time);
                const timeStr = activityTime.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', hour12: true });
                const label = activity.type === 'in' ? 'Clock In' : 'Clock Out';
                const workingFrom = activity.working_from ? `<span class="ms-1 px-2 py-0 bg-light rounded text-muted" style="font-size:10px;">${activity.working_from}</span>` : '';

                const hasGPS = activity.lat && activity.lng;

                timelineHTML += `
                    <div class="timeline-item-card fade-in" style="animation-delay: ${idx * 0.05}s">
                        <div class="timeline-radio"></div>
                        <div class="timeline-content">
                            <div class="timeline-type">
                                ${label} ${workingFrom}
                            </div>
                            <div class="timeline-time">${timeStr}</div>
                        </div>
                        <div class="action-btn-group">
                            ${hasGPS ? `
                                <button class="action-btn-sm btn-map" title="View Map" onclick="viewOnMap(${activity.lat}, ${activity.lng}, '${label}', '${timeStr}')">
                                    <i class="bi bi-eye-fill"></i>
                                </button>
                                <button class="action-btn-sm" title="Focus" onclick="viewOnMap(${activity.lat}, ${activity.lng}, '${label}', '${timeStr}')">
                                    <i class="bi bi-crosshair"></i>
                                </button>
                            ` : ''}
                            <div class="dropdown">
                                <button class="action-btn-sm" data-bs-toggle="dropdown" data-bs-boundary="viewport" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                    <li><a class="dropdown-item py-2" href="#"><i class="bi bi-pencil-square me-2"></i> Edit Time</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item py-2 text-danger" href="javascript:void(0)" onclick="deleteLog(${activity.id})"><i class="bi bi-trash3 me-2"></i> Delete Log</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                `;
            });
        } else {
            if (activityCount) activityCount.textContent = '0 activities today';
            timelineHTML = '<div class="text-center py-5 text-muted fw-medium border rounded-4 bg-white shadow-sm">No activities recorded today</div>';
        }

        if (activityTimeline) activityTimeline.innerHTML = timelineHTML;
    }

    // --- DELETE LOG FUNCTION ---
    window.deleteLog = function (logId) {
        if (!confirm('Are you sure you want to delete this activity? This cannot be undone.')) return;

        const formData = new FormData();
        formData.append('log_id', logId);

        fetch('delete_attendance_log.php', {
            method: 'POST',
            body: formData
        })
            .then(res => {
                if (!res.ok) throw new Error('Network response was not ok: ' + res.status);
                return res.text(); // Get as text first to handle empty or junk
            })
            .then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Server returned non-JSON:', text);
                    throw new Error('Invalid JSON response from server');
                }
            })
            .then(data => {
                if (data.success) {
                    if (window.showToast) window.showToast('success', 'Activity deleted successfully');
                    else alert('Activity deleted successfully');

                    if (typeof refreshAttendanceTable === 'function') {
                        refreshAttendanceTable();
                        if (attendanceModal) attendanceModal.hide();
                    } else {
                        location.reload();
                    }
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(err => {
                console.error('Delete Error:', err);
                alert('Deletion failed: ' + err.message);
            });
    };

    // --- GLOBAL MAP FUNCTIONS ---
    window.viewOnMap = function (lat, lng, type, time) {
        initModals();
        if (!locationMapModal) return;

        locationMapModal.show();

        // Initialize Dedicated Map if not exists
        if (!dedicatedMap) {
            dedicatedMap = L.map('attendance_map_dedicated', {
                zoomControl: true,
                attributionControl: false
            });

            roadLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(dedicatedMap);
            satelliteLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}');

            // Map/Satellite Toggles
            const btnRoad = document.getElementById('btnMapRoad');
            const btnSat = document.getElementById('btnMapSatellite');

            if (btnRoad && btnSat) {
                btnRoad.onclick = () => {
                    dedicatedMap.removeLayer(satelliteLayer);
                    dedicatedMap.addLayer(roadLayer);
                    btnRoad.classList.add('active');
                    btnSat.classList.remove('active');
                };
                btnSat.onclick = () => {
                    dedicatedMap.removeLayer(roadLayer);
                    dedicatedMap.addLayer(satelliteLayer);
                    btnSat.classList.add('active');
                    btnRoad.classList.remove('active');
                };
            }
        }

        // Clear existing markers (keeping it simple for single point focus)
        dedicatedMap.eachLayer((layer) => {
            if (layer instanceof L.Marker) dedicatedMap.removeLayer(layer);
        });

        // Add Modern Marker
        // Color based on type
        const isOut = type.toLowerCase().includes('out');
        const markerColor = isOut ? '#ef4444' : '#10b981';

        const markerHtml = `
            <div style="background-color: ${markerColor}; width: 14px; height: 14px; border: 3px solid white; border-radius: 50%; box-shadow: 0 0 10px rgba(0,0,0,0.5);"></div>
        `;
        const icon = L.divIcon({ html: markerHtml, className: '', iconSize: [14, 14], iconAnchor: [7, 7] });

        L.marker([lat, lng], { icon: icon })
            .addTo(dedicatedMap)
            .bindPopup(`<div class="p-1"><strong>${type}</strong><br>${time}</div>`)
            .openPopup();

        dedicatedMap.setView([lat, lng], 18);

        // Final invalidate for safety
        setTimeout(() => dedicatedMap.invalidateSize(), 300);
    };

    // --- UTILITIES & EVENT HANDLERS ---
    function formatDate(dateStr) {
        return new Date(dateStr).toLocaleDateString('en-GB', { day: '2-digit', month: '2-digit', year: 'numeric' });
    }

    document.addEventListener('click', function (e) {
        const cell = e.target.closest('.att-clickable');
        if (!cell) return;

        const status = (cell.getAttribute('data-status') || '').toUpperCase();

        // Skip for Week Off and Holiday as requested
        if (status === 'WO' || status === 'H') return;

        const empName = cell.getAttribute('data-emp-name');
        const empRole = cell.getAttribute('data-emp-role');
        const date = cell.getAttribute('data-date');
        currentEmpId = cell.getAttribute('data-emp-id');
        currentDate = date;

        if (status === 'A') {
            // If absent, show mark attendance modal directly
            if (typeof showMarkAttendanceModal === 'function') {
                showMarkAttendanceModal(currentEmpId, currentDate);
            }
            return;
        }

        // Otherwise (P, L, EG, etc.), show details modal
        initModals();

        // Prefill modal placeholders
        const modalEmpName = document.getElementById('modalEmpName');
        const modalEmpRole = document.getElementById('modalEmpRole');
        const modalDate = document.getElementById('modalDate');
        const timeline = document.getElementById('activityTimeline');
        const clockInBox = document.getElementById('clockInBox');
        const clockOutBox = document.getElementById('clockOutBox');
        const totalBox = document.getElementById('totalWorkBox');

        if (modalEmpName) modalEmpName.textContent = empName;
        if (modalEmpRole) modalEmpRole.textContent = empRole;
        if (modalDate) modalDate.textContent = formatDate(date);

        if (timeline) timeline.innerHTML = '<div class="text-center py-5"><div class="spinner-border text-primary" role="status"></div></div>';
        if (clockInBox) clockInBox.style.display = 'none';
        if (clockOutBox) clockOutBox.style.display = 'none';
        if (totalBox) totalBox.style.display = 'none';

        attendanceModal.show();

        fetch(`get_attendance_details.php?emp_id=${currentEmpId}&date=${currentDate}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    populateModal(data);
                } else {
                    if (timeline) timeline.innerHTML = '<div class="text-center py-5 text-muted">No details found</div>';
                }
            })
            .catch(err => {
                console.error(err);
                if (timeline) timeline.innerHTML = '<div class="text-center py-5 text-danger">Error loading details</div>';
            });
    });

    // Handle Date Header Click
    document.addEventListener('click', function (e) {
        const header = e.target.closest('.att-header-date');
        if (!header) return;

        const date = header.getAttribute('data-date');
        if (date && typeof showMarkAttendanceModal === 'function') {
            showMarkAttendanceModal(null, date);
        }
    });

    // Initial check
    setTimeout(initModals, 500);
})();
