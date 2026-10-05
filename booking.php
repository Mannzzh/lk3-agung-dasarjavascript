<?php
requireLogin();
$db = getDB();

$schedule_id = (int)($_GET['schedule_id'] ?? 0);
$stmt = $db->prepare("SELECT s.*, m.title, m.poster, m.duration, m.genre, m.rating,
    st.name AS studio_name
FROM schedules s
JOIN movies m ON s.movie_id = m.id
LEFT JOIN studios st ON s.studio_id = st.id
WHERE s.id = ?");
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch();
if (!$schedule) {
    $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Jadwal tidak ditemukan.'];
    header('Location: index.php?page=movies');
    exit;
}

$booked_stmt = $db->prepare("SELECT seats FROM bookings WHERE schedule_id = ? AND status != 'cancelled'");
$booked_stmt->execute([$schedule_id]);
$booked_seats = [];
foreach ($booked_stmt->fetchAll() as $b) {
    $booked_seats = array_merge($booked_seats, json_decode($b['seats'], true) ?? []);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_seats = $_POST['seats'] ?? [];
    $payment_method = $_POST['payment_method'] ?? '';

    if (empty($selected_seats)) {
        $error = 'Pilih minimal 1 kursi.';
    } elseif (count($selected_seats) > 8) {
        $error = 'Maksimal 8 kursi per pemesanan.';
    } elseif (!$payment_method) {
        $error = 'Pilih metode pembayaran.';
    } else {
        $conflicts = array_intersect($selected_seats, $booked_seats);
        if (!empty($conflicts)) {
            $error = 'Beberapa kursi sudah dipesan orang lain. Silakan pilih kursi lain.';
        } else {
            $total = count($selected_seats) * (int)$schedule['price'];
            $discount = 0;

            if (empty($error)) {

                $booking_fee = (int)getSetting('booking_fee');
                $final_total = $total - $discount + $booking_fee;
                $code = generateBookingCode();

                $ins = $db->prepare(
                    "INSERT INTO bookings (user_id, schedule_id, seats, total_price, promo_code, discount, status, payment_method, booking_code)
                     VALUES (?,?,?,?,?,?, 'confirmed', ?, ?)"
                );
                // promo_code kosong karena promo dihapus
                $promo_code_db = '';
            $discount_db = 0;

                $ins->execute([

                    $_SESSION['user_id'],
                    $schedule_id,
                    json_encode($selected_seats),
                    $final_total,
                    $promo_code_db,
                    $discount_db,
                    $payment_method,
                    $code
                ]);

                $booking_id = (int)$db->lastInsertId();


                $seat_str = implode(', ', $selected_seats);
                addNotification(
                    $_SESSION['user_id'],
                    '🎬 Tiket Berhasil Dipesan!',
                    "Kode booking: {$code}. Film: {$schedule['title']}. Kursi: {$seat_str}. Tanggal: {$schedule['show_date']} {$schedule['show_time']}. Total: " . formatPrice($final_total),
                    'success'
                );

                $_SESSION['flash'] = ['type' => 'success', 'msg' => "Tiket berhasil dipesan! Kode: {$code}"];

                header('Location: index.php?page=receipt&booking_id=' . $booking_id);
                exit;
            }
        }
    }
}

$total_rows = 8;
$total_cols = 10;
$seats_per_row = range('A', chr(ord('A') + $total_rows - 1));
?>

<style>
.booking-page { padding: 2rem 0 4rem; }
.booking-header { background: var(--bg2); border-bottom: 1px solid var(--border); padding: 1.5rem 0; margin-bottom: 2rem; }
.booking-title { font-family: 'Bebas Neue', sans-serif; font-size: 2rem; letter-spacing: 2px; }
.booking-grid { display: grid; grid-template-columns: 1fr 360px; gap: 2rem; }
.seat-section { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); padding: 1.5rem; }
.screen-label {
    text-align: center; margin-bottom: 2rem;
    font-size: 0.75rem; text-transform: uppercase; letter-spacing: 3px; color: var(--text2);
}
.screen-bar {
    height: 6px; background: linear-gradient(90deg, transparent, var(--accent), transparent);
    border-radius: 3px; margin-bottom: 8px; box-shadow: 0 0 20px rgba(230,57,70,0.5);
}
.seat-grid { display: grid; gap: 6px; margin-bottom: 1.5rem; }
.seat-row { display: flex; align-items: center; gap: 6px; }
.seat-row-label { width: 24px; text-align: center; font-size: 0.75rem; color: var(--text2); font-family: 'Space Mono', monospace; flex-shrink: 0; }
.seat-spacer { width: 12px; flex-shrink: 0; }
.seat {
    width: 36px; height: 34px; border-radius: 6px 6px 4px 4px;
    border: 1px solid var(--border2); background: var(--bg3);
    cursor: pointer; transition: all 0.15s; font-size: 0.7rem;
    display: flex; align-items: center; justify-content: center;
    color: var(--text2); font-family: 'Space Mono', monospace;
    flex-shrink: 0;
}
.seat:hover:not(.seat-booked) { background: rgba(160,160,160,0.35); border-color: var(--accent); color: var(--text); }
.seat.seat-selected { background: rgba(160,160,160,0.35); border-color: rgba(160,160,160,0.75); color: #ffffff; }
.seat.seat-booked { background: rgba(231,76,60,0.35); border-color: rgba(231,76,60,0.75); color: #ffffff; cursor: not-allowed; }
.seat.seat-booked.seat-vip { background: rgba(255,215,0,0.35); border-color: rgba(255,215,0,0.9); color: #ece9e4; cursor: not-allowed; }
.seat.seat-vip { border-color: rgba(255,215,0,0.3); }
.seat.seat-vip:not(.seat-booked):not(.seat-selected) { background: rgba(255,215,0,0.08); }
.seat.seat-vip.seat-selected { background: var(--gold); border-color: var(--gold); color: #1a0f00; }
.legend { display: flex; gap: 1.25rem; justify-content: center; flex-wrap: wrap; }
.legend-item { display: flex; align-items: center; gap: 6px; font-size: 0.75rem; color: var(--text2); }
.legend-box { width: 20px; height: 18px; border-radius: 4px; border: 1px solid var(--border2); }
.lb-available { background: var(--bg3); }
.lb-selected { background: rgba(160,160,160,0.35); border-color: rgba(160,160,160,0.75); }
.lb-booked { background: rgba(231,76,60,0.35); border-color: rgba(231,76,60,0.75); }
.lb-vip { background: rgba(255,215,0,0.15); border-color: rgba(255,215,0,0.3); }

.order-card { background: var(--card); border: 1px solid var(--border); border-radius: var(--radius2); padding: 1.5rem; position: sticky; top: 80px; }
.order-title { font-family: 'Bebas Neue', sans-serif; font-size: 1.5rem; margin-bottom: 1.25rem; }
.order-movie { display: flex; align-items: center; gap: 1rem; margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid var(--border); }
.order-movie-emoji { font-size: 2.5rem; background: var(--bg3); border-radius: 10px; width: 56px; height: 56px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.order-movie-title { font-weight: 700; font-size: 0.95rem; margin-bottom: 4px; }
.order-movie-info { font-size: 0.8rem; color: var(--text2); }
.order-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; font-size: 0.9rem; }
.order-row.total { font-weight: 700; font-size: 1.05rem; border-top: 1px solid var(--border); padding-top: 0.75rem; margin-top: 0.75rem; }
.error-box { background: rgba(231,76,60,0.12); border: 1px solid rgba(231,76,60,0.3); color: var(--danger); padding: 10px 14px; border-radius: var(--radius); font-size: 0.875rem; margin-bottom: 1rem; }
.selected-display { min-height: 36px; background: var(--bg3); border: 1px solid var(--border); border-radius: var(--radius); padding: 8px 12px; font-family: 'Space Mono', monospace; font-size: 0.8rem; color: var(--text2); margin-bottom: 0.75rem; }
.pay-methods { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; margin-bottom: 1rem; }
.pay-method { border: 2px solid var(--border2); border-radius: var(--radius); padding: 10px; text-align: center; cursor: pointer; transition: all 0.2s; }
.pay-method:hover { border-color: var(--accent); }
.pay-method input { display: none; }
.pay-method input:checked + .pay-label { color: var(--accent); }
.pay-method:has(input:checked) { border-color: var(--accent); background: rgba(230,57,70,0.08); }
.pay-label { display: block; font-size: 0.75rem; font-weight: 600; cursor: pointer; }
.pay-icon { font-size: 1.5rem; display: block; margin-bottom: 4px; }

@media(max-width:900px) { .booking-grid { grid-template-columns: 1fr; } .order-card { position: static; } }
</style>

<div class="booking-page">
    <div class="booking-header">
        <div class="container">
            <div class="booking-title">🎟️ PEMESANAN <span style="color:var(--accent)">TIKET</span></div>
            <?php $studio = $schedule['studio_name'] ?? ($schedule['studio'] ?? ($schedule['cinema_studio'] ?? '-')); ?>
            <p style="color:var(--text2);font-size:0.9rem;margin-top:4px;">
                <?= htmlspecialchars($schedule['title']) ?> — <?= date('d M Y', strtotime($schedule['show_date'])) ?>, <?= date('H:i', strtotime($schedule['show_time'])) ?> | <?= htmlspecialchars($studio) ?>
            </p>
        </div>
    </div>

    <div class="container">
        <?php if (!empty($error)): ?><div class="error-box">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

        <form method="POST" id="bookingForm">
            <div class="booking-grid">
                <!-- SEAT MAP -->
                <div class="seat-section">
                    <div class="screen-label">
                        <div class="screen-bar"></div>
                        LAYAR / SCREEN
                    </div>

                    <div class="seat-grid" id="seatGrid">
                        <?php foreach ($seats_per_row as $ri => $row): ?>
                        <div class="seat-row">
                            <div class="seat-row-label"><?= $row ?></div>
                            <?php for ($col = 1; $col <= $total_cols; $col++):
                                $seat_id = $row . $col;
                                $is_vip = in_array($row, ['D','E']);
                                $is_booked = in_array($seat_id, $booked_seats);
                                if ($col == 5) echo '<div class="seat-spacer"></div>';
                                $class = 'seat' . ($is_vip ? ' seat-vip' : '') . ($is_booked ? ' seat-booked' : '');
                            ?>
                            <div class="<?= $class ?>"
                                 data-seat="<?= $seat_id ?>"
                                 data-price="<?= (int)$schedule['price'] ?>"
                                 onclick="<?= $is_booked ? '' : "toggleSeat(this)" ?>">
                                <?= $col ?>
                            </div>
                            <?php endfor; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="legend">
                        <div class="legend-item"><div class="legend-box lb-available"></div> Tersedia</div>
                        <div class="legend-item"><div class="legend-box lb-selected"></div> Dipilih</div>
                        <div class="legend-item"><div class="legend-box lb-booked"></div> Terisi</div>
                        <div class="legend-item"><div class="legend-box lb-vip"></div> Baris VIP (D-E)</div>
                    </div>
                </div>

                <!-- ORDER SUMMARY -->
                <div class="order-card">
                    <div class="order-title">RINGKASAN PESANAN</div>

                    <div class="order-movie">
                        <div class="order-movie-emoji">
                            <img
                                src="<?= htmlspecialchars($schedule['poster']) ?>"
                                alt="Poster <?= htmlspecialchars($schedule['title'] ?? '') ?>"
                                style="width:56px;height:56px;object-fit:cover;border-radius:10px;display:block;"
                            />
                        </div>
                        <div>
                            <div class="order-movie-title"><?= htmlspecialchars($schedule['title']) ?></div>
                            <div class="order-movie-info">
                                <?= date('d M Y', strtotime($schedule['show_date'])) ?><br>
                                <?= date('H:i', strtotime($schedule['show_time'])) ?> • <?= htmlspecialchars($studio) ?>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Kursi Dipilih</label>
                        <div class="selected-display" id="selectedDisplay">Belum ada kursi dipilih</div>
                        <div id="seatInputs"></div>
                    </div>

                    <div class="order-row">
                        <span id="seatCountLabel">Tiket (0 kursi)</span>
                        <span id="subtotalLabel">Rp 0</span>
                    </div>

                    <div class="order-row">
                        <span>Biaya Layanan</span>
                        <span><?= formatPrice((int)getSetting('booking_fee')) ?></span>
                    </div>

                    <div class="order-row total">
                        <span>TOTAL</span>
                        <span id="totalLabel" style="color:var(--gold)">Rp 0</span>
                    </div>

                    <div class="form-group mt-2">
                        <label class="form-label">Metode Pembayaran</label>
                        <div class="pay-methods">
                            <?php $methods = [['💳','transfer','Transfer Bank'],['📱','gopay','GoPay'],['💰','ovo','OVO'],['🏪','minimarket','Minimarket']]; ?>
                            <?php foreach ($methods as [$icon, $val, $lbl]): ?>
                            <label class="pay-method">
                                <input type="radio" name="payment_method" value="<?= $val ?>" <?= (($_POST['payment_method']??'')===$val)?'checked':'' ?>>
                                <span class="pay-label"><span class="pay-icon"><?= $icon ?></span><?= $lbl ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-gold btn-block btn-lg" id="submitBtn" disabled>
                        Pesan Tiket 🎟️
                    </button>

                    <div id="seatWarning" style="text-align:center;font-size:0.8rem;color:var(--text2);margin-top:0.75rem;">
                        ℹ️ Pilih minimal 1 kursi sebelum memesan.
                    </div>

                    <p style="text-align:center;font-size:0.75rem;color:var(--text2);margin-top:0.5rem;">
                        Dengan memesan, Anda menyetujui syarat & ketentuan CineMax
                    </p>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const PRICE = <?= (int)$schedule['price'] ?>;
const VIP_EXTRA_PERCENT = 40;
const BOOKING_FEE = <?= (int)getSetting('booking_fee') ?>;

let selectedSeats = [];

function toggleSeat(el) {
    const seat = el.dataset.seat;
    if (el.classList.contains('seat-selected')) {
        el.classList.remove('seat-selected');
        selectedSeats = selectedSeats.filter(s => s !== seat);
    } else {
        if (selectedSeats.length >= 8) { alert('Maksimal 8 kursi per pemesanan!'); return; }
        el.classList.add('seat-selected');
        selectedSeats.push(seat);
    }
    updateSummary();
}

function updateSummary() {
    let subtotal = 0;
    for (const s of selectedSeats) {
        const row = s[0];
        const seatPrice = (row === 'D' || row === 'E') ? Math.round(PRICE * (1 + VIP_EXTRA_PERCENT/100)) : PRICE;
        subtotal += seatPrice;
    }

    const total = subtotal + BOOKING_FEE;

    const count = selectedSeats.length;
    document.getElementById('seatCountLabel').textContent = `Tiket (${count} kursi)`;
    document.getElementById('subtotalLabel').textContent = formatRp(subtotal);
    document.getElementById('totalLabel').textContent = formatRp(total < 0 ? 0 : total);
    document.getElementById('selectedDisplay').textContent = count ? selectedSeats.sort().join(', ') : 'Belum ada kursi dipilih';

    const inputs = document.getElementById('seatInputs');
    inputs.innerHTML = selectedSeats.map(s => `<input type="hidden" name="seats[]" value="${s}">`).join('');

    document.getElementById('submitBtn').disabled = count === 0;
}

function formatRp(n) {
    return 'Rp ' + n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Safety guard: kalau submit dilakukan tanpa kursi terpilih (mis. via cara lain)
document.getElementById('bookingForm').addEventListener('submit', function (e) {
    const seatCount = selectedSeats.length;
    if (seatCount === 0) {
        e.preventDefault();
        alert('Pilih minimal 1 kursi sebelum memesan.');
        return false;
    }
});
</script>

