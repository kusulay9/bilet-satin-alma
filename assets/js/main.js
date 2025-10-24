document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    const seats = document.querySelectorAll('.seat.available');
    seats.forEach(seat => {
        seat.addEventListener('click', function() {
            if (this.classList.contains('selected')) {
                this.classList.remove('selected');
            } else {
                document.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
                this.classList.add('selected');
            }
            updateSelectedSeat();
        });
    });

    function updateSelectedSeat() {
        const selectedSeat = document.querySelector('.seat.selected');
        const seatInfo = document.getElementById('selected-seat-info');
        if (selectedSeat && seatInfo) {
            seatInfo.textContent = `Seçilen Koltuk: ${selectedSeat.textContent}`;
            seatInfo.style.display = 'block';
        } else if (seatInfo) {
            seatInfo.style.display = 'none';
        }
    }

    const couponForm = document.getElementById('coupon-form');
    if (couponForm) {
        couponForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const couponCode = document.getElementById('coupon-code').value;
            if (couponCode.trim()) {
                validateCoupon(couponCode);
            }
        });
    }

    function validateCoupon(code) {
        const loading = document.getElementById('coupon-loading');
        const result = document.getElementById('coupon-result');
        
        loading.style.display = 'block';
        result.style.display = 'none';

        fetch('/api/validate-coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ code: code })
        })
        .then(response => response.json())
        .then(data => {
            loading.style.display = 'none';
            result.style.display = 'block';
            
            if (data.success) {
                result.className = 'alert alert-success';
                result.innerHTML = `
                    <strong>Kupon Geçerli!</strong><br>
                    İndirim: %${data.discount_percent}<br>
                    Yeni Fiyat: ${data.new_price} ₺
                `;
                updatePrice(data.new_price);
            } else {
                result.className = 'alert alert-danger';
                result.innerHTML = `<strong>Hata:</strong> ${data.message}`;
            }
        })
        .catch(error => {
            loading.style.display = 'none';
            result.style.display = 'block';
            result.className = 'alert alert-danger';
            result.innerHTML = '<strong>Hata:</strong> Kupon doğrulanırken bir hata oluştu.';
        });
    }

    function updatePrice(newPrice) {
        const priceElement = document.getElementById('final-price');
        if (priceElement) {
            priceElement.textContent = newPrice + ' ₺';
        }
    }

    let selectedSeat = null;
    let couponDiscount = 0;

    const tripSeats = document.querySelectorAll('.seat.available');
    tripSeats.forEach(seat => {
        seat.addEventListener('click', function() {
            document.querySelectorAll('.seat.selected').forEach(s => s.classList.remove('selected'));
            
            this.classList.add('selected');
            selectedSeat = this.dataset.seat;
            
            const seatNoInput = document.getElementById('seat_no');
            const selectedSeatDisplay = document.getElementById('selected-seat-display');
            const purchaseBtn = document.getElementById('purchase-btn');
            const seatText = document.getElementById('seat-text');
            const selectedSeatInfo = document.getElementById('selected-seat-info');
            
            if (seatNoInput) seatNoInput.value = selectedSeat;
            if (selectedSeatDisplay) selectedSeatDisplay.textContent = `Koltuk ${selectedSeat}`;
            if (purchaseBtn) purchaseBtn.disabled = false;
            if (seatText) seatText.textContent = `Seçilen Koltuk: ${selectedSeat}`;
            if (selectedSeatInfo) selectedSeatInfo.style.display = 'block';
            
            calculateFinalPrice();
        });
    });

    const validateCouponBtn = document.getElementById('validate-coupon-btn');
    if (validateCouponBtn) {
        validateCouponBtn.addEventListener('click', validateCoupon);
    }

    function calculateFinalPrice() {
        const basePriceElement = document.getElementById('base-price');
        const finalPriceElement = document.getElementById('final-price');
        
        if (basePriceElement && finalPriceElement) {
            const basePriceText = basePriceElement.textContent;
            const basePrice = parseFloat(basePriceText.replace(/[^\d.,]/g, '').replace(',', '.'));
            const discountAmount = (basePrice * couponDiscount) / 100;
            const finalPrice = basePrice - discountAmount;
            
            finalPriceElement.textContent = finalPrice.toFixed(2) + ' ₺';
        }
    }

    function validateCoupon() {
        const couponCodeInput = document.getElementById('coupon-code');
        const resultDiv = document.getElementById('coupon-result');
        
        if (!couponCodeInput || !resultDiv) return;
        
        const couponCode = couponCodeInput.value.trim();
        if (!couponCode) {
            alert('Lütfen kupon kodunu girin.');
            return;
        }
        
        const validCoupons = {
            'WELCOME10': 10,
            'METRO20': 20,
            'ULUSOY15': 15
        };
        
        if (validCoupons[couponCode]) {
            couponDiscount = validCoupons[couponCode];
            resultDiv.className = 'alert alert-success mt-2';
            resultDiv.innerHTML = `<strong>Kupon Geçerli!</strong><br>İndirim: %${couponDiscount}`;
            resultDiv.style.display = 'block';
            calculateFinalPrice();
        } else {
            couponDiscount = 0;
            resultDiv.className = 'alert alert-danger mt-2';
            resultDiv.innerHTML = '<strong>Hata:</strong> Geçersiz kupon kodu.';
            resultDiv.style.display = 'block';
            calculateFinalPrice();
        }
    }

    let ticketToCancel = null;

    window.cancelTicket = function(ticketId) {
        ticketToCancel = ticketId;
        const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
        modal.show();
    };

    window.showLoginRequired = function() {
        const modal = new bootstrap.Modal(document.getElementById('loginModal'));
        modal.show();
    };

    window.editCoupon = function(coupon) {
        document.getElementById('edit_coupon_id').value = coupon.id;
        document.getElementById('edit_code').value = coupon.code;
        document.getElementById('edit_discount_percent').value = coupon.discount_percent;
        document.getElementById('edit_limit').value = coupon.limit;
        document.getElementById('edit_expiry_date').value = coupon.expiry_date;
        
        const modal = new bootstrap.Modal(document.getElementById('editCouponModal'));
        modal.show();
    };

    window.deleteCoupon = function(couponId) {
        document.getElementById('delete_coupon_id').value = couponId;
        const modal = new bootstrap.Modal(document.getElementById('deleteCouponModal'));
        modal.show();
    };

    const codeInputs = document.querySelectorAll('#code, #edit_code');
    codeInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    });

    window.editFirm = function(firm) {
        document.getElementById('edit_firm_id').value = firm.id;
        document.getElementById('edit_firm_name').value = firm.name;
        
        const modal = new bootstrap.Modal(document.getElementById('editFirmModal'));
        modal.show();
    };

    window.deleteFirm = function(firmId) {
        document.getElementById('delete_firm_id').value = firmId;
        const modal = new bootstrap.Modal(document.getElementById('deleteFirmModal'));
        modal.show();
    };

    window.editTrip = function(trip) {
        document.getElementById('edit_trip_id').value = trip.id;
        document.getElementById('edit_from_city').value = trip.from_city;
        document.getElementById('edit_to_city').value = trip.to_city;
        document.getElementById('edit_date').value = trip.date;
        document.getElementById('edit_time').value = trip.time;
        document.getElementById('edit_duration').value = trip.duration;
        document.getElementById('edit_price').value = trip.price;
        document.getElementById('edit_seat_count').value = trip.seat_count;
        
        const modal = new bootstrap.Modal(document.getElementById('editTripModal'));
        modal.show();
    };

    window.deleteTrip = function(tripId) {
        document.getElementById('delete_trip_id').value = tripId;
        const modal = new bootstrap.Modal(document.getElementById('deleteTripModal'));
        modal.show();
    };

    const confirmCancelBtn = document.getElementById('confirmCancel');
    if (confirmCancelBtn) {
        confirmCancelBtn.addEventListener('click', function() {
            if (ticketToCancel) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/user/cancel-ticket.php';
                
                const ticketIdInput = document.createElement('input');
                ticketIdInput.type = 'hidden';
                ticketIdInput.name = 'ticket_id';
                ticketIdInput.value = ticketToCancel;
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                
                form.appendChild(ticketIdInput);
                form.appendChild(csrfInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
});

function formatCurrency(amount) {
    return new Intl.NumberFormat('tr-TR', {
        style: 'currency',
        currency: 'TRY'
    }).format(amount);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('tr-TR');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('tr-TR');
}

async function apiCall(url, options = {}) {
    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API call failed:', error);
        throw error;
    }
}

document.addEventListener('DOMContentLoaded', function() {
    $('#from_city, #to_city').select2({
        placeholder: 'Şehir seçin...',
        allowClear: true,
        language: {
            noResults: function() {
                return "Sonuç bulunamadı";
            },
            searching: function() {
                return "Aranıyor...";
            }
        }
    });
});
