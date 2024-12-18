<link rel="stylesheet" href="assets/dist/css/bootstrap.min.css">
<?php
session_start();
include "koneksi.php";

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

    // Daftar perintah berbahaya
    $dangerous_commands = [
        'exec', 'system', 'shell_exec', 'passthru', 'unlink', 'rmdir',
        'fopen', 'file_put_contents', 'fwrite', 'include', 'require',
        'include_once', 'require_once', 'eval', 'popen', 'proc_open',
        'chmod', 'chown', 'rename', 'base64_decode', 'file_get_contents',
        'glob', 'assert', 'dl'
    ];

    // Pengecekan perintah berbahaya
    foreach ($dangerous_commands as $command) {
        if (stripos($user_code, $command) !== false) {
            echo "<div class='border border-danger mb-2 p-1'>Eksekusi gagal! Kode program mengandung perintah berbahaya!</div>";
            exit();
        }
    }

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

    // Mencari secret testcase
    $query_s_testcase = mysqli_query($koneksi, "SELECT * FROM s_testcase where id_soal='$id_soal'");
    $data_s_testcase = mysqli_fetch_assoc($query_s_testcase);

    // Nama file untuk secret test code
    $s_test_file = "/s_testcode_" . $_SESSION['siswa'] . "_" . $id_soal . "_" . $data_s_testcase["id_s_testcase"] . ".php";
    $s_test_path = $folder . $s_test_file;

    // Membuat file s_test_code dan menulis kode di dalamnya
    $s_test_code = '<?php
include "' . $filecode . '";
echo ' . $soal["function_name"] . '(' . $data_s_testcase["input"] . ');';
    file_put_contents($s_test_path, $s_test_code);

    // Menyiapkan perintah untuk menjalankan script dengan batasan waktu
    $command = "php " . escapeshellarg($s_test_path);
    $descriptorspec = [
        0 => ["pipe", "r"], // stdin
        1 => ["pipe", "w"], // stdout
        2 => ["pipe", "w"]  // stderr
    ];

    // Membuka proses
    $process = proc_open($command, $descriptorspec, $pipes);
    if (is_resource($process)) {
        // Menutup stdin
        fclose($pipes[0]);

        // Menetapkan batas waktu untuk mengeksekusi skrip
        $timeout = 3; // dalam detik
        $start_time = time();
        $output = '';
        while (!feof($pipes[1])) {
            $output .= fgets($pipes[1], 4096);
            if ((time() - $start_time) > $timeout) {
                // Jika melebihi batas waktu, hentikan proses
                proc_terminate($process);
                $output = "Timeout";
                break;
            }
        }
        fclose($pipes[1]);
        fclose($pipes[2]);

        // Mendapatkan status keluar
        $return_value = proc_close($process);
    } else {
        $output = "Error executing test code.";
    }

    // Antisipasi untuk output berupa boolean
    if ($output == "1" && $data_s_testcase["output"] == "true") {
        $output = "true";
    } else if ($output == "" && $data_s_testcase["output"] == "false") {
        $output = "false";
    }

    if (trim($output) != $data_s_testcase["output"]) {
        echo "<div class='border border-danger mb-2 p-1'>
        <strong>Jawaban Salah! Perbaiki jawaban anda!</strong>
        <hr>
        Output: " . $output . "
        </div>";
    } else {
        // Mencari data dari tabel testcase
        $select_testcase = mysqli_query($koneksi, "SELECT * FROM testcase where id_soal='$id_soal'");
        $jumlah_testcase = mysqli_num_rows($select_testcase);
        $testcase_benar = 0;

        while ($data_testcase = mysqli_fetch_assoc($select_testcase)) {
            // Nama file untuk testcode format (testcode_idsiswa_idsoal_idtestcase)
            $test_file = "/testcode_" . $_SESSION['siswa'] . "_" . $id_soal . "_" . $data_testcase["id_testcase"] . ".php";
            $test_path = $folder . $test_file;

            // Membuat file test_code dan menulis isi kode di dalamnya
            $test_code = '<?php
include "' . $filecode . '";
echo ' . $soal["function_name"] . '(' . $data_testcase["input"] . ');';
            file_put_contents($test_path, $test_code);

            // Menyiapkan perintah untuk menjalankan script dengan batasan waktu
            $command = "php " . escapeshellarg($test_path);
            $descriptorspec = [
                0 => ["pipe", "r"], // stdin
                1 => ["pipe", "w"], // stdout
                2 => ["pipe", "w"]  // stderr
            ];

            // Membuka proses
            $process = proc_open($command, $descriptorspec, $pipes);
            if (is_resource($process)) {
                // Menutup stdin
                fclose($pipes[0]);

                // Menetapkan batas waktu untuk mengeksekusi skrip
                $timeout = 3; // dalam detik
                $start_time = time();
                $output = '';
                while (!feof($pipes[1])) {
                    $output .= fgets($pipes[1], 4096);
                    if ((time() - $start_time) > $timeout) {
                        // Jika melebihi batas waktu, hentikan proses
                        proc_terminate($process);
                        $output = "Timeout";
                        break;
                    }
                }
                fclose($pipes[1]);
                fclose($pipes[2]);

                // Mendapatkan status keluar
                $return_value = proc_close($process);
            } else {
                $output = "Error executing test code.";
            }

            // Antisipasi untuk output berupa boolean
            if ($output == "1" && $data_testcase["output"] == "true") {
                $output = "true";
            } else if ($output == "" && $data_testcase["output"] == "false") {
                $output = "false";
            }

            // Mengecek hasil output
            if (trim($output) == $data_testcase["output"]) {
                // Menambah jawaban benar
                $testcase_benar += 1;
                echo "<div class='border border-success mb-2 p-1'>Test berhasil! Input: " . $data_testcase["input"] . " Output: " . $output . " Output diharapkan: " . $data_testcase["output"] . "</div>";
            } else {
                echo "<div class='border border-danger mb-2 p-1'><strong>Test Gagal! Input: " . $data_testcase["input"] . " Output: " . $output . " Output diharapkan: " . $data_testcase["output"] . "</strong></div>";
            }
        }
        ?>

        <form action="simpan_jawaban.php" method="post" onsubmit="<?php if ($testcase_benar < $jumlah_testcase) {
            $test_result = "gagal";
            echo "return confirm('Hasil test masih ada kesalahan! Tetap kirim jawaban?')";
        } else {
            $test_result = "lulus";
        } ?>">
            <textarea name="hasil" id="" style="display: none;"><?= $user_code; ?></textarea>
            <input type="hidden" name="test_result" id="" value="<?php echo $test_result; ?>">
            <input type="hidden" name="id_siswa" id="" value="<?= $_SESSION['siswa']; ?>">
            <input type="hidden" name="id_soal" id="" value="<?= $id_soal; ?>">
            <input type="hidden" name="id_ujian" id="" value="<?= $soal["id_ujian"]; ?>">
            <input type="hidden" name="id_s_testcase" id="" value="<?= $data_s_testcase["id_s_testcase"];?>">
            <input type="submit" class="btn btn-lg btn-success mt-3" value="Kirim Jawaban">
        </form>
        <?php
    }
}
?>
