</main><!-- End main content -->

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<!-- Chart.js (loaded on all pages, lightweight) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<!-- Custom JS -->
<script src="<?php echo BASE_URL; ?>/js/app.js"></script>

<?php
// Display flash messages via SweetAlert2
$flashSuccess = getFlashMessage('success');
$flashError = getFlashMessage('error');
$flashInfo = getFlashMessage('info');
$flashWarning = getFlashMessage('warning');
?>

<?php if ($flashSuccess): ?>
<script>showToast('success', <?php echo json_encode($flashSuccess); ?>);</script>
<?php
endif; ?>

<?php if ($flashError): ?>
<script>showToast('error', <?php echo json_encode($flashError); ?>);</script>
<?php
endif; ?>

<?php if ($flashInfo): ?>
<script>showToast('info', <?php echo json_encode($flashInfo); ?>);</script>
<?php
endif; ?>

<?php if ($flashWarning): ?>
<script>showToast('warning', <?php echo json_encode($flashWarning); ?>);</script>
<?php
endif; ?>

</body>
</html>
