<?php
include 'head.php';
include 'navbar.php';
include 'sidebar.php';

?>
 
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <section class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1>Daftar Gadai Barang</h1>
        </div>
      </div>
    </div><!-- /.container-fluid -->
  </section>

  <!-- Main content -->
  <section class="content">

    <!-- Default box -->
    <div class="card">
      <div class="card-header">
        <h3 class="card-title">List of Pawned Items</h3>

        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
            <i class="fas fa-minus"></i>
          </button>
          <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
            <i class="fas fa-times"></i>
          </button>
        </div>
      </div>
      <div class="card-body">
        <table id="pawnedItemsTable" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>No</th>
              <th>Item Name</th>
              <th>Item Description</th>
              <th>Image</th>
              <th>Owner Name</th>
              <th>Pawn Date</th>
              <th>Redeem Date</th>
              <th>Status</th>
              <th>Loan Amount</th>
              <th>Interest Rate</th>
              <th>Total Amount Due</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <!-- Example static data, replace with dynamic data from your database -->
            <tr>
              <td>1</td>
              <td>Gold Necklace</td>
              <td>24K Gold Necklace</td>
              <td><img src="path/to/image1.jpg" alt="Gold Necklace" width="50" class="img-thumbnail" data-toggle="modal" data-target="#imageModal" onclick="showImage(this)"></td>
              <td>John Doe</td>
              <td>2025-03-01</td>
              <td>2025-06-01</td>
              <td>Active</td>
              <td>$500</td>
              <td>5%</td>
              <td>$525</td>
              <td>
                <button class="btn btn-primary btn-sm">Edit</button>
                <button class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <tr>
              <td>2</td>
              <td>Smartphone</td>
              <td>iPhone 12</td>
              <td><img src="path/to/image2.jpg" alt="Smartphone" width="50" class="img-thumbnail" data-toggle="modal" data-target="#imageModal" onclick="showImage(this)"></td>
              <td>Jane Smith</td>
              <td>2025-02-15</td>
              <td>2025-05-15</td>
              <td>Redeemed</td>
              <td>$800</td>
              <td>3%</td>
              <td>$824</td>
              <td>
                <button class="btn btn-primary btn-sm">Edit</button>
                <button class="btn btn-danger btn-sm">Delete</button>
              </td>
            </tr>
            <!-- End of example static data -->
          </tbody>
        </table>
      </div>
      <!-- /.card-body -->
      <div class="card-footer">
        Footer
      </div>
      <!-- /.card-footer-->
    </div>
    <!-- /.card -->

  </section>
  <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php
include 'script.php';
?>

<!-- Include DataTables CSS and JS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script>

<!-- Initialize DataTable -->
<script>
$(document).ready( function () {
    $('#pawnedItemsTable').DataTable();
});
</script>

<!-- Modal HTML -->
<div class="modal fade" id="imageModal" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <img id="modalImage" src="" alt="Image Preview" class="img-fluid">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript to handle image click and show modal -->
<script>
function showImage(element) {
    var src = element.src;
    document.getElementById('modalImage').src = src;
}
</script>

<!-- Include Bootstrap CSS and JS for modal functionality -->
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>