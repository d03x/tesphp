<?php
session_start();
include "koneksi.php";
if (!isset($_SESSION['siswa']))
{
    header("location: index.php");
}
else
{
    //variabel session
    $id_siswa = $_SESSION["siswa"];
    //ambil data siswa
    $qry_siswa = mysqli_query($koneksi, "SELECT * FROM siswa where id_siswa='$id_siswa'");
    //pecah menjadi array
    $data_siswa = mysqli_fetch_assoc($qry_siswa);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home | Tes PHP</title>
    <link rel="stylesheet" href="assets/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/2.0.0/css/dataTables.dataTables.min.css">
    <style>
        /* CSS tambahan sesuai kebutuhan */
        .navbar {
            background-color: #007bff; /* Warna biru PHP */
        }
        .navbar-nav, .navbar-brand  {
            padding-left: 20px;
            padding-right: 20px;
        }
        .navbar-nav .nav-item {
            margin-right: 10px;
        }
        .navbar-nav .nav-link {
            color: #fff; /* Warna tulisan putih */
        }
        .logout {
            background-color: #dc3545; /* Warna merah untuk menu logout */
            border-radius: 5px;
        }
        .row {
            border : solid 1px #CCC;
            border-radius : 10px;
            box-shadow: 5px 5px 5px #DDD;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            background-color: #f8f9fa; /* Warna latar belakang footer */
            text-align: center;
            padding: 10px 0;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <a class="navbar-brand" href="#">Logo</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item active">
                    <a class="nav-link" href="#">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#"><?= $data_siswa["nama"];?></a>
                </li>
                <li class="nav-item logout">
                    <a class="nav-link" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12 p-50">
            <h2>Selamat Datang <?= $data_siswa["nama"];?></h2>
            <h4>List Ujian</h4>
                <table id="myTable" class="table table-striped table-bordered" style="width:100%">
                    <thead>
                        <tr>
                            <th>Nama Ujian</th>
                            <th>Jumlah Soal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
    <?php
    //ambil data ujian berdasarkan kelas siswa dan statusnya aktif
    $qry_ujian = mysqli_query($koneksi, "SELECT * FROM ujian WHERE id_kelas='$data_siswa[id_kelas]' AND status='aktif'");
    // pecah menjadi array kemudian looping
    while($data_ujian=mysqli_fetch_assoc($qry_ujian))
    {
        ?>
                        <tr>
                            <td><?= $data_ujian['nama_ujian'];?></td>
                            <td>30</td>
                            <td><?= "<a href='soal.php?id_ujian=" . $data_ujian['id_ujian'] . "'>Kerjakan</a>"?></td>
                        </tr>
    <?php
        }    
    ?>                        
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
<!-- Footer -->
<div class="footer">
        <p>Website Ujian Pemrograman PHP | Dikembangkan oleh Apep Wahyudin</p>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.datatables.net/2.0.0/js/dataTables.min.js"></script>
    <!-- Inisialisasi DataTables -->
    <script>
        let table = new DataTable('#myTable');
    </script>
</body>
</html>