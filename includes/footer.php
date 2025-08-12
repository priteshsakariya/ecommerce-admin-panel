</div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <div class="float-right d-none d-sm-block">
            <strong>Version:</strong> <?php echo APP_VERSION; ?>
        </div>
        <strong>Copyright &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>.</strong> All rights reserved.
    </footer>
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.6.4/dist/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2.0/dist/js/adminlte.min.js"></script>

<!-- Custom JavaScript -->
<script>
$(document).ready(function() {
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Confirm delete actions
    $('.delete-btn').click(function(e) {
        if (!confirm('Are you sure you want to delete this item?')) {
            e.preventDefault();
        }
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 5000);
    
    // Form validation
    $('.needs-validation').each(function() {
        $(this).on('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            $(this).addClass('was-validated');
        });
    });
    
    // Dynamic variant management
    let variantCount = 0;
    $('#add-variant').click(function() {
        variantCount++;
        const variantHtml = `
            <div class="variant-row" id="variant-${variantCount}">
                <div class="row">
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="variants[${variantCount}][name]" placeholder="Variant Name (e.g., Size)">
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="variants[${variantCount}][value]" placeholder="Value (e.g., Large)">
                    </div>
                    <div class="col-md-2">
                        <input type="text" class="form-control" name="variants[${variantCount}][sku]" placeholder="SKU">
                    </div>
                    <div class="col-md-2">
                        <input type="number" class="form-control" name="variants[${variantCount}][stock]" placeholder="Stock" min="0">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-danger remove-variant" data-target="variant-${variantCount}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        $('#variants-container').append(variantHtml);
    });
    
    $(document).on('click', '.remove-variant', function() {
        const target = $(this).data('target');
        $(`#${target}`).remove();
    });
});

// Status update function
function updateStatus(type, id, status) {
    $.ajax({
        url: `ajax/update_status.php`,
        method: 'POST',
        data: {
            type: type,
            id: id,
            status: status,
            _token: '<?php echo generateCSRFToken(); ?>'
        },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                location.reload();
            } else {
                alert('Error updating status: ' + data.message);
            }
        },
        error: function() {
            alert('Network error occurred');
        }
    });
}

// Quick stock update
function updateStock(variantId, quantity) {
    $.ajax({
        url: 'ajax/update_stock.php',
        method: 'POST',
        data: {
            variant_id: variantId,
            quantity: quantity,
            _token: '<?php echo generateCSRFToken(); ?>'
        },
        success: function(response) {
            const data = JSON.parse(response);
            if (data.success) {
                $(`#stock-${variantId}`).text(quantity);
                showAlert('Stock updated successfully', 'success');
            } else {
                showAlert('Error updating stock: ' + data.message, 'danger');
            }
        },
        error: function() {
            showAlert('Network error occurred', 'danger');
        }
    });
}

// Show alert function
function showAlert(message, type) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    `;
    $('.content-header').after(alertHtml);
}
</script>

<?php if (isset($additionalJS)): ?>
    <?php echo $additionalJS; ?>
<?php endif; ?>

</body>
</html>