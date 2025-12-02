<?php
require 'koneksi.php';

// Ambil semua users untuk dropdown
$res = $mysqli->query("SELECT id, nim, nama, foto FROM users ORDER BY nama");
$users = $res->fetch_all(MYSQLI_ASSOC);

// pilih user id dari GET
$uid = isset($_GET['uid']) ? (int)$_GET['uid'] : null;
if (!$uid && count($users) > 0) {
    $uid = $users[0]['id'];
}

// tab yang aktif (seperti di admin)
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'profile';
$allowedTabs = ['profile', 'biodata', 'pendidikan', 'pengalaman', 'keahlian', 'hobi'];
if (!in_array($tab, $allowedTabs)) $tab = 'profile';

// fungsi bantu
function esc($s){ return htmlspecialchars($s); }
$profile = null;
if ($uid) {
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i',$uid);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // ambil biodata
    $biodata = [];
    $stmt = $mysqli->prepare("SELECT * FROM biodata WHERE nim = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $profile['nim']);
    $stmt->execute();
    $biodata = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // pendidikan
    $pendidikan = [];
    $stmt = $mysqli->prepare("SELECT * FROM pendidikan WHERE nim = ? ORDER BY tahun DESC");
    $stmt->bind_param('s', $profile['nim']);
    $stmt->execute();
    $pendidikan = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // pengalaman
    $pengalaman = [];
    $stmt = $mysqli->prepare("SELECT * FROM pengalaman WHERE nim = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $profile['nim']);
    $stmt->execute();
    $pengalaman = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // keahlian
    $keahlian = [];
    $stmt = $mysqli->prepare("SELECT * FROM keahlian WHERE nim = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $profile['nim']);
    $stmt->execute();
    $keahlian = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // hobi (ambil semua hobi rows)
    $hobi = [];
    $stmt = $mysqli->prepare("SELECT * FROM hobi WHERE nim = ? ORDER BY created_at DESC");
    $stmt->bind_param('s', $profile['nim']);
    $stmt->execute();
    $hobi = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // footer (ambil terakhir)
    $footer = null;
    $stmt = $mysqli->prepare("SELECT * FROM footer WHERE nim = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param('s', $profile['nim']);
    $stmt->execute();
    $footer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Profil Personil</title>
  <link rel="stylesheet" href="style.css" />
  <style>
  /* Tambahan CSS untuk tab navigation di frontend */
  .main-content {
    max-width: 1400px;
    margin: 30px auto;
    padding: 0 30px;
  }
  
  .tab-nav-frontend {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid rgba(180, 120, 255, 0.25);
    background: rgba(25, 27, 60, 0.7);
    padding: 15px;
    border-radius: 12px;
    backdrop-filter: blur(10px);
  }
  
  .tab-nav-frontend a {
    padding: 10px 18px;
    border-radius: 10px;
    text-decoration: none;
    background: rgba(255, 255, 255, 0.05);
    color: #dcd7ff;
    border: 1px solid rgba(170, 100, 255, 0.2);
    transition: all 0.3s ease;
    font-weight: 500;
  }
  
  .tab-nav-frontend a:hover {
    background: rgba(150, 90, 255, 0.3);
    color: #fff;
  }
  
  .tab-nav-frontend a.active {
    background: rgba(150, 60, 255, 0.5);
    color: #fff;
    font-weight: 600;
    box-shadow: 0 0 15px rgba(150, 60, 255, 0.5);
    border: 1px solid rgba(200, 150, 255, 0.4);
  }
  
  .user-selector {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
    padding: 15px;
    background: rgba(30, 32, 70, 0.6);
    border-radius: 12px;
  }
  
  .user-selector label {
    color: #dcd7ff;
    font-weight: 500;
  }
  
  .user-selector select {
    padding: 10px 15px;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(170, 100, 255, 0.3);
    border-radius: 8px;
    color: #fff;
    font-family: "Poppins", sans-serif;
    min-width: 250px;
  }
  
  .user-selector select:focus {
    outline: none;
    border-color: #8743ff;
  }
  
  .tab-content {
    background: rgba(25, 27, 60, 0.7);
    padding: 30px;
    border-radius: 14px;
    border: 1px solid rgba(170, 100, 255, 0.2);
    box-shadow: 0 0 25px rgba(90, 60, 200, 0.15);
    backdrop-filter: blur(12px);
    min-height: 400px;
  }
  
  .profile-header {
    display: flex;
    align-items: center;
    gap: 25px;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid rgba(180, 120, 255, 0.2);
  }
  
  .profile-photo-large {
    width: 150px;
    height: 150px;
    border-radius: 15px;
    object-fit: cover;
    border: 3px solid #8743ff;
    box-shadow: 0 0 25px rgba(135, 67, 255, 0.3);
  }
  
  .profile-info h2 {
    color: #dfcaff;
    font-size: 2.2rem;
    margin-bottom: 8px;
    text-shadow: 0 0 10px rgba(140, 87, 255, 0.4);
  }
  
  .profile-info p {
    color: #dcdcdc;
    font-size: 1.1rem;
  }
  
  .content-card {
    background: rgba(30, 32, 70, 0.5);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid rgba(170, 100, 255, 0.15);
    margin-bottom: 20px;
  }
  
  .content-card h4 {
    color: #dfcaff;
    font-size: 1.3rem;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(180, 120, 255, 0.2);
  }
  
  .content-card p {
    color: #dcdcdc;
    line-height: 1.7;
  }
  
  .list-simple {
    list-style: none;
    padding: 0;
  }
  
  .list-simple li {
    background: rgba(255, 255, 255, 0.03);
    padding: 15px;
    margin-bottom: 12px;
    border-radius: 8px;
    border: 1px solid rgba(170, 100, 255, 0.15);
    color: #dcdcdc;
    font-size: 1rem;
  }
  
  .list-simple li strong {
    color: #dfcaff;
    font-weight: 600;
  }
  
  .hobbies-list {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-top: 15px;
  }
  
  .hobby-tag {
    background: rgba(135, 67, 255, 0.2);
    color: #dfcaff;
    padding: 8px 15px;
    border-radius: 20px;
    border: 1px solid rgba(135, 67, 255, 0.4);
    font-size: 0.95rem;
  }
  
  .no-data {
    text-align: center;
    padding: 40px;
    color: #9ca3af;
    font-style: italic;
    font-size: 1.1rem;
  }
  
  /* Footer styling */
  .main-footer-content {
    margin-top: 40px;
    background: rgba(25, 27, 60, 0.7);
    border-radius: 14px;
    border: 1px solid rgba(170, 100, 255, 0.2);
    padding: 30px;
    backdrop-filter: blur(12px);
    text-align: center;
  }
  
  .footer-quote {
    font-style: italic;
    color: #d4caff;
    margin-bottom: 20px;
    padding: 20px;
    border-left: 4px solid #8743ff;
    background: rgba(135, 67, 255, 0.1);
    border-radius: 8px;
    font-size: 1.1rem;
  }
  
  .footer-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-bottom: 20px;
  }
  
  .footer-links a {
    color: #b98bff;
    text-decoration: none;
    padding: 8px 16px;
    border-radius: 6px;
    background: rgba(255, 255, 255, 0.05);
    transition: all 0.3s;
  }
  
  .footer-links a:hover {
    background: rgba(150, 90, 255, 0.3);
    color: #fff;
  }
  
  .footer-copyright {
    color: #d4caff;
    font-size: 0.95rem;
  }
  </style>
</head>
<body>
<header class="site-header">
  <div class="header-inner">
    <?php if ($profile && !empty($profile['foto']) && file_exists(__DIR__.'/uploads/'.$profile['foto'])): ?>
      <img src="<?php echo 'uploads/'.esc($profile['foto']); ?>" alt="Foto Profil" class="WIN2025">
    <?php else: ?>
      <img src="fallback-profile.png" alt="Foto Profil" class="WIN2025">
    <?php endif; ?>
    <div>
      <h1 class="site-title">Profil Personil</h1>
      <p class="site-sub">Lihat profil dan riwayat singkat</p>
    </div>
  </div>
</header>

<div class="main-content">
  <!-- User Selector -->
  <div class="user-selector">
    <label for="uid">Pilih Profil:</label>
    <select name="uid" id="uid" onchange="window.location.href='?uid='+this.value+'&tab=<?php echo $tab; ?>'">
      <?php foreach($users as $u): ?>
        <option value="<?php echo $u['id']; ?>" <?php echo ($u['id']==$uid)?'selected':''; ?>>
          <?php echo esc($u['nim'].' — '.$u['nama']); ?>
        </option>
      <?php endforeach; ?>
    </select>
    <a href="admin_personil.php" style="margin-left:auto;padding:8px 16px;background:rgba(135, 67, 255, 0.8);color:white;border-radius:8px;text-decoration:none;">Admin Panel</a>
  </div>

  <!-- Tab Navigation -->
  <div class="tab-nav-frontend">
    <?php foreach($allowedTabs as $t): ?>
      <a href="?uid=<?php echo $uid; ?>&tab=<?php echo $t; ?>" class="<?php echo ($t===$tab)?'active':''; ?>">
        <?php 
          $tabNames = [
            'profile' => 'Profil',
            'biodata' => 'Biodata',
            'pendidikan' => 'Pendidikan',
            'pengalaman' => 'Pengalaman',
            'keahlian' => 'Keahlian',
            'hobi' => 'Hobi'
          ];
          echo $tabNames[$t];
        ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Tab Content -->
  <div class="tab-content">
    <?php if (!$profile): ?>
      <div class="no-data">
        <h2>Tidak ada profil</h2>
        <p>Silakan tambahkan user di panel admin.</p>
      </div>
    <?php else: ?>
    
      <!-- PROFILE TAB -->
      <?php if ($tab === 'profile'): ?>
        <div class="profile-header">
          <?php if ($profile && !empty($profile['foto']) && file_exists(__DIR__.'/uploads/'.$profile['foto'])): ?>
            <img src="<?php echo 'uploads/'.esc($profile['foto']); ?>" alt="Foto Profil" class="profile-photo-large">
          <?php else: ?>
            <img src="fallback-profile.png" alt="Foto Profil" class="profile-photo-large">
          <?php endif; ?>
          <div class="profile-info">
            <h2><?php echo esc($profile['nama']); ?></h2>
            <p><strong>NIM:</strong> <?php echo esc($profile['nim']); ?></p>
          </div>
        </div>
        
        <!-- Ringkasan semua data -->
        <div class="content-card">
          <h4>Ringkasan Profil</h4>
          <p>Selamat datang di halaman profil <?php echo esc($profile['nama']); ?>. Gunakan tab di atas untuk melihat detail informasi.</p>
          
          <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
            <div>
              <h5 style="color: #dfcaff; margin-bottom: 10px;">Statistik</h5>
              <ul style="color: #dcdcdc;">
                <li>Biodata: <?php echo count($biodata); ?> Bio</li>
                <li>Pendidikan: <?php echo count($pendidikan); ?> jenjang</li>
                <li>Pengalaman: <?php echo count($pengalaman); ?> pengalaman</li>
                <li>Keahlian: <?php echo count($keahlian); ?> keahlian</li>
              </ul>
            </div>
            <div>
              <h5 style="color: #dfcaff; margin-bottom: 10px;">Hobi</h5>
              <div class="hobbies-list">
                <?php if (!empty($hobi)): ?>
                  <?php 
                  $allHobbies = [];
                  foreach($hobi as $h) {
                    $items = preg_split('/[;|,]+/', $h['hobi']);
                    foreach($items as $it) {
                      if(trim($it) != '') {
                        $allHobbies[] = trim($it);
                      }
                    }
                  }
                  $allHobbies = array_unique($allHobbies);
                  foreach(array_slice($allHobbies, 0, 5) as $hobby): ?>
                    <span class="hobby-tag"><?php echo esc($hobby); ?></span>
                  <?php endforeach; ?>
                  <?php if(count($allHobbies) > 5): ?>
                    <span class="hobby-tag">+<?php echo count($allHobbies)-5; ?> lainnya</span>
                  <?php endif; ?>
                <?php else: ?>
                  <span style="color: #9ca3af;">Belum ada hobi</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      
      <!-- BIODATA TAB -->
      <?php elseif ($tab === 'biodata'): ?>
        <div class="profile-header">
          <div class="profile-info">
            <h2><?php echo esc($profile['nama']); ?></h2>
            <p>Biodata & Informasi Pribadi</p>
          </div>
        </div>
        
        <?php if (!empty($biodata)): ?>
          <?php foreach($biodata as $b): ?>
            <div class="content-card">
              <h4><?php echo esc($b['judul']); ?></h4>
              <p><?php echo nl2br(esc($b['isi'])); ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-data">
            <p>Belum ada data biodata untuk <?php echo esc($profile['nama']); ?></p>
          </div>
        <?php endif; ?>
      
      <!-- PENDIDIKAN TAB -->
      <?php elseif ($tab === 'pendidikan'): ?>
        <div class="profile-header">
          <div class="profile-info">
            <h2><?php echo esc($profile['nama']); ?></h2>
            <p>Riwayat Pendidikan</p>
          </div>
        </div>
        
        <?php if (!empty($pendidikan)): ?>
          <ul class="list-simple">
            <?php foreach($pendidikan as $p): ?>
              <li>
                <strong><?php echo esc($p['institusi']); ?></strong><br>
                <span><?php echo esc($p['jurusan']); ?></span><br>
                <small>Tahun: <?php echo esc($p['tahun']); ?></small>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="no-data">
            <p>Belum ada data pendidikan untuk <?php echo esc($profile['nama']); ?></p>
          </div>
        <?php endif; ?>
      
      <!-- PENGALAMAN TAB -->
      <?php elseif ($tab === 'pengalaman'): ?>
        <div class="profile-header">
          <div class="profile-info">
            <h2><?php echo esc($profile['nama']); ?></h2>
            <p>Pengalaman Kerja & Organisasi</p>
          </div>
        </div>
        
        <?php if (!empty($pengalaman)): ?>
          <?php foreach($pengalaman as $pg): ?>
            <div class="content-card">
              <h4><?php echo esc($pg['judul']); ?></h4>
              <p><?php echo nl2br(esc($pg['isi'])); ?></p>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="no-data">
            <p>Belum ada data pengalaman untuk <?php echo esc($profile['nama']); ?></p>
          </div>
        <?php endif; ?>
      
      <!-- KEAHLIAN TAB -->
      <?php elseif ($tab === 'keahlian'): ?>
        <div class="profile-header">
          <div class="profile-info">
            <h2><?php echo esc($profile['nama']); ?></h2>
            <p>Keahlian & Kompetensi</p>
          </div>
        </div>
        
        <?php if (!empty($keahlian)): ?>
          <ul class="list-simple">
            <?php foreach($keahlian as $k): ?>
              <li>
                <strong><?php echo esc($k['judul']); ?></strong><br>
                <span><?php echo esc($k['isi']); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
        <?php else: ?>
          <div class="no-data">
            <p>Belum ada data keahlian untuk <?php echo esc($profile['nama']); ?></p>
          </div>
        <?php endif; ?>
      
      <!-- HOBI TAB -->
      <?php elseif ($tab === 'hobi'): ?>
        <div class="profile-header">
          <div class="profile-info">
            <h2><?php echo esc($profile['nama']); ?></h2>
            <p>Hobi & Minat</p>
          </div>
        </div>
        
        <?php if (!empty($hobi)): ?>
          <div class="content-card">
            <h4>Daftar Hobi</h4>
            <div class="hobbies-list">
              <?php 
              $allHobbies = [];
              foreach($hobi as $h) {
                $items = preg_split('/[;|,]+/', $h['hobi']);
                foreach($items as $it) {
                  if(trim($it) != '') {
                    $allHobbies[] = trim($it);
                  }
                }
              }
              $allHobbies = array_unique($allHobbies);
              foreach($allHobbies as $hobby): ?>
                <span class="hobby-tag"><?php echo esc($hobby); ?></span>
              <?php endforeach; ?>
            </div>
          </div>
          
          <?php if (count($hobi) > 1): ?>
            <div class="content-card">
              <h4>Detail Hobi</h4>
              <?php foreach($hobi as $index => $h): ?>
                <div style="margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid rgba(170, 100, 255, 0.1);">
                  <strong>Set <?php echo $index + 1; ?>:</strong>
                  <?php 
                  $items = preg_split('/[;|,]+/', $h['hobi']);
                  foreach($items as $it): 
                    if(trim($it) != ''): ?>
                      <span style="display: inline-block; background: rgba(255,255,255,0.05); padding: 5px 10px; margin: 5px; border-radius: 6px;">
                        <?php echo esc(trim($it)); ?>
                      </span>
                  <?php endif; endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="no-data">
            <p>Belum ada data hobi untuk <?php echo esc($profile['nama']); ?></p>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  
  <!-- Footer Content (ditampilkan di semua tab) -->
  <?php if ($footer): ?>
    <div class="main-footer-content">
      <?php if (!empty($footer['quotes'])): ?>
        <div class="footer-quote">
          "<?php echo esc($footer['quotes']); ?>"
        </div>
      <?php endif; ?>
      
      <div class="footer-links">
        <?php if (!empty($footer['instagram'])): ?>
          <a href="<?php echo esc($footer['instagram']); ?>" target="_blank">Instagram</a>
        <?php endif; ?>
        
        <?php if (!empty($footer['youtube'])): ?>
          <a href="<?php echo esc($footer['youtube']); ?>" target="_blank">YouTube</a>
        <?php endif; ?>
      </div>
      
      <div class="footer-copyright">
        <p><?php echo esc($footer['copyright_text']); ?></p>
        <p>© <?php echo date('Y'); ?> Web Profil Personil</p>
      </div>
    </div>
  <?php endif; ?>
</div>

</body>
</html>