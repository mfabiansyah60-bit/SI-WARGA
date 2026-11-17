<?php
// app/Helpers/Mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../../vendor/autoload.php';

class Mailer {
    
    private static function getMailerInstance($debug = 0) {
        $mail = new PHPMailer(true);
        
        // 1. Baca dari .env, sesuaiin sama akun Gmail baru lu
        $email_pengirim = getenv('MAIL_USERNAME') ?: 'haio53319@gmail.com'; 
        $app_password   = getenv('MAIL_PASSWORD') ?: 'jqsnvspbewcskdsm'; 
        $mail_host      = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        
        // 2. Baca Port & Enkripsi dari .env
        $mail_port      = (int)(getenv('MAIL_PORT') ?: 587); 
        $mail_enc       = getenv('MAIL_ENCRYPTION') ?: 'tls';
        $from_name      = getenv('MAIL_FROM_NAME') ?: 'Pemerintah Desa';
        
        // Matiin SSL Verifikasi lokal (kalo windows suka ngeblokir)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        $mail->isSMTP();
        $mail->Host       = $mail_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $email_pengirim;
        $mail->Password   = $app_password;
        $mail->Port       = $mail_port;

        // 3. DETEKSI OTOMATIS: Kalo port 465 pake SMTPS, kalo 587 pake STARTTLS (TLS)
        if ($mail_port === 465) {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }

        // 4. Debug Mode (1 = Client, 2 = Server/Detail)
        if ($debug > 0) {
            $mail->SMTPDebug = $debug;
            $mail->Debugoutput = function($str, $level) {
                error_log("SMTP DEBUG [$level]: $str");
            };
        }

        $mail->CharSet    = 'UTF-8';
        $mail->Priority   = 1; 
        $mail->XMailer    = 'Pemerintah Desa System';

        $mail->setFrom($email_pengirim, $from_name);
        return $mail;
    }

    // 1. Fungsi OTP (Tidak diubah)
    public static function sendOTP($to_email, $to_name, $otp_code) {
        $mail = self::getMailerInstance();
        try {
            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = 'Kode Verifikasi SIWARGA';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; text-align: center; border: 1px solid #eee; border-radius: 10px;'>
                    <h2 style='color: #333;'>Verifikasi Akun SIWARGA</h2>
                    <p>Halo <b>{$to_name}</b>, ini adalah kode OTP Anda:</p>
                    <h1 style='color: #2563eb; letter-spacing: 5px; font-size: 36px;'>{$otp_code}</h1>
                    <p style='color: #888; font-size: 12px;'>Kode ini berlaku selama 5 menit. Jangan berikan kode ini kepada siapapun.</p>
                </div>
            ";
            $mail->send();
            return ['success' => true, 'message' => 'OTP terkirim'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Gagal kirim OTP: ' . $mail->ErrorInfo];
        }
    }

    /**
     * Bersihkan teks dari tautan internal (action=hapus) dan URL localhost
     */
    public static function cleanContent($text) {
        if (empty($text)) return '';
        // 1. Hapus link action internal seperti ?action=hapus atau /pengumuman?action=hapus
        $text = preg_replace('/(?:\/[a-zA-Z0-9_\-]+)?\?[^\s\n\r]*action=(?:hapus|delete)[^\s\n\r]*/i', '', $text);
        // 2. Hapus URL localhost / IP lokal
        $text = preg_replace('/https?:\/\/(?:localhost|127\.0\.0\.1|0\.0\.0\.0)(?::\d+)?(?:\/[^\s\n\r]*)?/i', '', $text);
        // 3. Rapikan whitespace berlebih
        $text = preg_replace('/[ \t]{2,}/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        return trim($text);
    }

    // 2. Fungsi Pengumuman Batch (Email Resmi SIWARGA)
    public static function sendPengumumanBatch($penerima_email, $judul, $isi, $role, $nama_desa, $nama_ketua, $no_rt, $no_rw) {
        $mail = self::getMailerInstance();
        $berhasil_masuk = 0;
        $gagal = 0;

        $instansi = "PEMERINTAH DESA " . strtoupper($nama_desa);
        if ($role === 'rt') {
            $sub_instansi = "PENGURUS RT " . $no_rt . " / RW " . $no_rw;
            $jabatan = "Ketua RT " . $no_rt;
        } elseif ($role === 'rw') {
            $sub_instansi = "PENGURUS RW " . $no_rw;
            $jabatan = "Ketua RW " . $no_rw;
        } else {
            $sub_instansi = "KANTOR KEPALA DESA";
            $jabatan = "Kepala Desa";
        }
        $tanggal_sekarang = date('d F Y');

        // Bersihkan konten email dari URL localhost / tautan hapus internal
        $cleanJudul = self::cleanContent($judul);
        $cleanIsi   = self::cleanContent($isi);

        // Deduplikasi dan validasi ketat setiap penerima email
        $processedEmails = [];
        foreach ($penerima_email as $penerima) {
            $email_tujuan = strtolower(trim($penerima['email'] ?? ''));
            if (empty($email_tujuan) || isset($processedEmails[$email_tujuan])) {
                continue;
            }

            if (filter_var($email_tujuan, FILTER_VALIDATE_EMAIL) && !str_ends_with($email_tujuan, '@siwarga.local')) {
                $mail->addBCC($email_tujuan, $penerima['name'] ?? '');
                $processedEmails[$email_tujuan] = true;
                $berhasil_masuk++;
            } else {
                $gagal++;
            }
        }

        if ($berhasil_masuk === 0) {
            return ['berhasil' => 0, 'gagal' => $gagal, 'error' => 'Tidak ada email valid'];
        }

        try {
            $mail->isHTML(true);
            $mail->Subject = 'Pengumuman Resmi: ' . $cleanJudul;
            $mail->Body = "
                <div style='font-family: \"Times New Roman\", Times, serif; color: #000; max-width: 700px; margin: 0 auto; padding: 20px; font-size: 15px; line-height: 1.6;'>
                    <div style='text-align: center; border-bottom: 3px double #000; padding-bottom: 10px; margin-bottom: 20px;'>
                        <h2 style='margin: 0; font-size: 20px; font-weight: bold;'>" . $instansi . "</h2>
                        <h3 style='margin: 5px 0 0 0; font-size: 18px; font-weight: normal;'>" . $sub_instansi . "</h3>
                        <p style='margin: 5px 0 0 0; font-size: 13px;'>Sistem Informasi Warga (SIWARGA)</p>
                    </div>
                    <table style='width: 100%; margin-bottom: 20px; font-size: 15px;'>
                        <tr><td style='width: 60%;'>Nomor &nbsp;&nbsp;&nbsp;: ..../SIWARGA/..../".date('Y')."</td><td style='text-align: right;'>" . $tanggal_sekarang . "</td></tr>
                        <tr><td>Lampiran : -</td><td></td></tr>
                        <tr><td>Perihal &nbsp;&nbsp;: <b>Pengumuman</b></td><td></td></tr>
                    </table>
                    <div style='margin-bottom: 20px;'>Kepada Yth.<br><b>Bapak/Ibu Warga</b><br>di Tempat</div>
                    <div style='margin-bottom: 30px;'>
                        <p>Dengan hormat,</p>
                        <p>Sehubungan dengan <b>" . htmlspecialchars($cleanJudul) . "</b>, kami sampaikan informasi sebagai berikut:</p>
                        <div style='background-color: #f8fafc; padding: 15px; border-left: 4px solid #64748b; margin: 15px 0;'>" . nl2br(htmlspecialchars($cleanIsi)) . "</div>
                        <p>Demikian pengumuman ini kami sampaikan. Mohon untuk dapat diperhatikan dan dilaksanakan sebagaimana mestinya.</p>
                    </div>
                    <div style='margin-top: 50px; text-align: right;'>
                        <p style='margin-bottom: 70px;'>Hormat kami,<br><b>" . $jabatan . "</b></p>
                        <p style='text-decoration: underline; font-weight: bold; margin: 0;'>" . strtoupper($nama_ketua) . "</p>
                    </div>
                </div>
            ";
            $mail->send();
            return ['berhasil' => $berhasil_masuk, 'gagal' => $gagal];
        } catch (Exception $e) {
            return ['berhasil' => 0, 'gagal' => ($berhasil_masuk + $gagal), 'error' => $mail->ErrorInfo];
        }
    }

    // 3. Fungsi Kirim Surat (Tidak diubah)
    public static function sendSurat($to_email, $to_name, $judul_surat, $nomor_surat, $jenis_surat, $nama_desa, $link_download = '', $pdf_content = null) {
        $mail = self::getMailerInstance();
        try {
            $mail->addAddress($to_email, $to_name);
            $mail->isHTML(true);
            $mail->Subject = 'Surat Resmi: ' . $judul_surat;
            
            $mail->Body = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #eee; border-radius: 10px; max-width: 600px; margin: 0 auto;'>
                    <h2 style='color: #2563eb; text-align: center;'>PEMERINTAH DESA " . strtoupper($nama_desa) . "</h2>
                    <hr style='border: 0; border-top: 2px solid #2563eb; margin: 15px 0;'>
                    <p>Halo <b>{$to_name}</b>,</p>
                    <p>Pengajuan surat Anda telah <b>disetujui</b>. Terlampir adalah file PDF surat resmi Anda.</p>
                    
                    <div style='background: #f8fafc; padding: 15px; border-left: 4px solid #2563eb; margin: 15px 0;'>
                        <table style='width: 100%;'>
                            <tr><td style='width: 120px;'>Nomor Surat</td><td>: <b>{$nomor_surat}</b></td></tr>
                            <tr><td>Jenis Surat</td><td>: {$jenis_surat}</td></tr>
                        </table>
                    </div>
                    
                    <p>Anda dapat mengunduh dan mencetak lampiran PDF tersebut kapan saja.</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #888; text-align: center;'>Email ini dikirim otomatis oleh Sistem SIWARGA.</p>
                </div>
            ";

            if ($pdf_content !== null) {
                $nama_file_pdf = 'Surat_' . preg_replace('/[^A-Za-z0-9]/', '_', $jenis_surat) . '.pdf';
                $mail->addStringAttachment($pdf_content, $nama_file_pdf);
            }

            $mail->send();
            return true;
        } catch (Exception $e) {
            return "Gagal: " . $mail->ErrorInfo;
        }
    }
}