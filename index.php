<?php require_once 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Hospital Bed Allocation System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
<style>
body { 
    background: linear-gradient(135deg, #e0f7fa 0%, #ffffff 100%);
    font-family: 'Segoe UI', sans-serif; 
    min-height: 100vh;
    padding-bottom: 30px;
}

.alert-position {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
    min-width: 250px;
}
h2 { font-weight:700; color:#343a40; text-align:center; margin-bottom:30px; }
.card {
    border-radius:15px; 
    box-shadow: 0 4px 20px rgba(0,0,0,0.08), 0 0 25px rgba(0, 123, 255, 0.05);
    transition:0.3s;
}
.card:hover { transform:translateY(-5px); box-shadow:0 10px 30px rgba(0,0,0,0.12); }
.dashboard-card {
    display:flex; align-items:center; justify-content:center; flex-direction:column;
    padding:25px; font-weight:600; color:#fff; border-radius:15px; position:relative;
}
.dashboard-card i {
    font-size:2rem; margin-bottom:10px;
}
.bed { min-height:60px; display:flex; align-items:center; justify-content:center; border-radius:10px; margin:3px; cursor:pointer; background:#fff; font-size:12px; font-weight:500; text-align:center; transition:0.3s; position:relative; }
.bed.empty { background:#e9ecef; }
.bed.full { background:#dc3545; color:#fff; }
.bed:hover { transform:scale(1.05); box-shadow:0 5px 12px rgba(0,0,0,0.12); }
.patient-avatar { width:42px; height:42px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:14px; margin:2px auto; border:2px solid #fff; cursor:grab; position:relative; transition:0.2s; }
.patient-avatar.high { background:#dc3545; color:#fff; }
.patient-avatar.medium { background:#ffc107; color:#212529; }
.patient-avatar.low { background:#6c757d; color:#fff; }
.patient-avatar .btn-delete { position:absolute; top:-5px; right:-5px; font-size:10px; padding:0 4px; border-radius:50%; }
#wardsContainer { min-height:250px; display:flex; flex-wrap:wrap; gap:15px; justify-content:center; }
.ward-card { background:#fff; padding:15px; border-radius:12px; min-width:180px; max-width:220px; flex:1; box-shadow:0 3px 12px rgba(0,0,0,0.08); transition:0.3s; }
.ward-card:hover { transform:translateY(-3px); box-shadow:0 6px 18px rgba(0,0,0,0.12); }
.form-section h5 { font-weight:700; margin-bottom:15px; }
input.form-control, select.form-select, button { border-radius:10px; }
button { font-weight:500; }
.priority-legend span { display:inline-flex; align-items:center; margin-right:10px; font-size:0.9rem; }
.priority-dot { width:12px; height:12px; border-radius:50%; display:inline-block; margin-right:5px; }
.priority-dot.high { background:#dc3545; }
.priority-dot.medium { background:#ffc107; }
.priority-dot.low { background:#6c757d; }

/* Responsive tweaks */
@media (max-width: 991px) {
  #wardsContainer { justify-content:center; }
  .dashboard-card { margin-bottom:15px; }
}
@media (max-width: 767px) {
  .form-section { margin-bottom:20px; }
  .dashboard-card { font-size:0.9rem; padding:20px; }
  .dashboard-card i { font-size:1.5rem; }
}
</style>
</head>
<body>
<div class="container mt-4">
  <h2 class="mb-4">Hospital Bed Allocation Dashboard</h2>

  <?php
  $totalBeds = $conn->query("SELECT SUM(capacity) AS total_beds FROM wards")->fetch_assoc()['total_beds'] ?? 0;
  $totalWards = $conn->query("SELECT COUNT(*) AS total_wards FROM wards")->fetch_assoc()['total_wards'] ?? 0;
  $totalPatients = $conn->query("SELECT COUNT(*) AS total_patients FROM patients")->fetch_assoc()['total_patients'] ?? 0;
  ?>

  <!-- Dashboard Metrics -->
  <div class="row mb-4 g-3">
    <div class="col-lg-4 col-md-6">
      <div class="dashboard-card bg-primary">
        <i class="bi bi-hospital"></i>
        <h6>Total Wards</h6>
        <h3><?php echo $totalWards; ?></h3>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="dashboard-card bg-success">
        <i class="bi bi-bed"></i>
        <h6>Total Beds</h6>
        <h3><?php echo $totalBeds; ?></h3>
      </div>
    </div>
    <div class="col-lg-4 col-md-6">
      <div class="dashboard-card bg-warning text-dark">
        <i class="bi bi-people"></i>
        <h6>Total Patients</h6>
        <h3><?php echo $totalPatients; ?></h3>
      </div>
    </div>
  </div>

  <!-- Forms Row -->
  <div class="row g-3">
    <div class="col-lg-4 col-md-6 form-section">
      <div class="card p-4">
        <h5>Add Ward</h5>
        <form id="wardForm">
          <input type="text" name="name" placeholder="Ward Name" class="form-control mb-3" required>
          <input type="number" name="capacity" placeholder="Capacity" class="form-control mb-3" required>
          <button class="btn btn-primary w-100">Add Ward</button>
        </form>
      </div>
    </div>

    <div class="col-lg-4 col-md-6 form-section">
      <div class="card p-4">
        <h5>Add Patient</h5>
        <form id="patientForm">
          <input type="text" name="name" placeholder="Patient Name" class="form-control mb-3" required>
          <input type="number" name="age" placeholder="Age" class="form-control mb-3" required>
          <select name="gender" class="form-select mb-3" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
          </select>
          <select name="priority" class="form-select mb-3" required>
            <option value="High">High</option>
            <option value="Medium" selected>Medium</option>
            <option value="Low">Low</option>
          </select>
          <button class="btn btn-success w-100">Add Patient</button>
        </form>
      </div>
    </div>

    <div class="col-lg-4 col-md-6 form-section">
      <div class="card p-4 d-flex flex-column justify-content-center align-items-center">
        <h5>Allocate Patients</h5>
        <button id="allocateBtn" class="btn btn-warning w-100 mb-2">Graph Coloring Allocation</button>
        <button id="reallocateBtn" class="btn btn-danger w-100">Reallocate All Patients</button>
      </div>
    </div>
  </div>

  <!-- Search & Filter -->
  <div class="row g-3 mt-4">
    <div class="col-md-6 col-12">
      <input type="text" id="searchPatient" class="form-control" placeholder="Search patient by name">
    </div>
    <div class="col-md-6 col-12">
      <select id="filterPriority" class="form-select">
        <option value="">Filter by Priority</option>
        <option value="High">High</option>
        <option value="Medium">Medium</option>
        <option value="Low">Low</option>
      </select>
    </div>
  </div>

  <!-- Priority Legend -->
  <div class="mb-3 mt-3 priority-legend">
    <h6>Priority Legend:</h6>
    <span><span class="priority-dot high"></span>High</span>
    <span><span class="priority-dot medium"></span>Medium</span>
    <span><span class="priority-dot low"></span>Low</span>
  </div>

  <!-- Wards Display -->
  <div class="row mt-4" id="wardsContainer">
    <!-- Ward cards populate dynamically -->
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="dashboard.js"></script>
</body>
</html>
