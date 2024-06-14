<link rel="stylesheet" href="assets/dist/css/bootstrap.min.css">
<?php
session_start();
include "koneksi.php";
$id_soal = $_POST["id_soal"];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil parameter dari URL dan membersihkannya
    $id_soal = filter_input(INPUT_POST, 'id_soal', FILTER_SANITIZE_NUMBER_INT);

    // Memeriksa apakah id_soal ada
    if ($id_soal) {
        // Query untuk mengambil data dari tabel soal
        $sql_soal = "SELECT test_code, function_name, id_ujian 
                 FROM soal 
                 WHERE status = 'aktif' AND id_soal = ?";

        // Menyiapkan statement
        $stmt_soal = $koneksi->prepare($sql_soal);
        if ($stmt_soal) {
            // Bind parameter dan eksekusi statement
            $stmt_soal->bind_param("i", $id_soal);
            $stmt_soal->execute();
            $result_soal = $stmt_soal->get_result();

            // Memeriksa apakah data soal ditemukan
            if ($result_soal->num_rows > 0) {
                $soal = $result_soal->fetch_assoc();
            } else {
                echo "<p>Soal tidak ditemukan atau tidak aktif.</p>";
                exit();
            }
        } else {
            echo "<p>Gagal menyiapkan query soal.</p>";
            exit();
        }
    } else {
        echo "<p>Parameter id_soal tidak valid.</p>";
        exit();
    }
    // Ambil kode dari textarea
    $user_code = str_replace("?>", "", $_POST['code']);

    // Sanitasi input
    $sanitized_code = htmlspecialchars($user_code, ENT_QUOTES, 'UTF-8');

    // Nama folder dan file yang akan dibuat
    $folder = 'code';
    $filecode = "codesiswa_" . $_SESSION['siswa'] . "_" . $id_soal . ".php";
    $code_path = $folder . '/' . $filecode;

    // Pastikan folder "code" ada, jika tidak, buat folder tersebut
    if (!is_dir($folder)) {
        mkdir($folder, 0777, true);
    }
    // Membuat file dan menulis isi kode ke dalamnya
    file_put_contents($code_path, $user_code);


    //Mencari data dari tabel testcase
    $select_testcase = mysqli_query($koneksi, "SELECT * FROM testcase where id_soal='$id_soal'");
    $jumlah_testcase = mysqli_num_rows($select_testcase);
    $testcase_benar = 0;
    while ($data_testcase = mysqli_fetch_assoc($select_testcase)) {

        //nama file untuk testcode format (testcode_idsiswa_idsoal_idtestcase)
        $test_file = "/testcode_" . $_SESSION['siswa'] . "_" . $id_soal . "_" . $data_testcase["id_testcase"] . ".php";
        $test_path = $folder . $test_file;

        //membuat file test_code dan menulis isi kode di dalamnya
        $test_code = '<?php
include "' . $filecode . '";
' . 'echo ' . $soal["function_name"] . '(' . $data_testcase["input"] . ');';
        file_put_contents($test_path, $test_code);

        //eksekusi file test_code
        $execute_testcode = shell_exec("php " . $test_path);
        if (trim($execute_testcode) == $data_testcase["output"]) {
            //menambah jawaban benar
            $testcase_benar += 1;
            echo "<div class='border border-success rounded mb-2 p-1'>Test berhasil! Input: " . $data_testcase["input"] . " Output diharapkan: " . $data_testcase["output"] . "</div>";
        } else {
            echo "<div class='border border-danger rounded mb-2 p-1'>Test GAGAL! Input: " . $data_testcase["input"] . " Output diharapkan: " . $data_testcase["output"] . "</div>";
        }

    }
    ?>

    <form action="simpan_jawaban.php" method="post"
        onsubmit="<?php if ($testcase_benar < $jumlah_testcase) {
            $test_result = "gagal";
            echo "return confirm('Hasil test masih ada kesalahan! Tetap kirim jawaban?')";
        }
        else {
            $test_result = "lulus";
        } ?>">
        <textarea name="hasil" id="" style="display: none;"><?= $user_code; ?></textarea>
        <input type="hidden" name="test_result" id="" value="<?php echo $test_result;?>">
        <input type="hidden" name="id_siswa" id="" value="<?= $_SESSION['siswa']; ?>">
        <input type="hidden" name="id_soal" id="" value="<?= $id_soal; ?>">
        <input type="hidden" name="id_ujian" id="" value="<?= $soal["id_ujian"]; ?>">
        <input type="submit" class="btn btn-lg btn-success mt-3" value="Kirim Jawaban">
    </form>
    <?php

}
