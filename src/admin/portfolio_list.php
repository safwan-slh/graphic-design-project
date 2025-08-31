<?php
require '../includes/db_connect.php';
require '../auth/auth.php';
requireRole('admin'); // ให้เข้าหน้านี้ได้เฉพาะ admin

// ดึงข้อมูลผลงานทั้งหมด
$sql = "SELECT p.*, s.service_name 
        FROM portfolios p 
        LEFT JOIN services s ON p.service_id = s.service_id 
        ORDER BY p.created_at DESC";

$result = $conn->query($sql); ?>

<!DOCTYPE html>
<html lang="th">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>จัดการผลงาน - Admin</title>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
	<link href="../../dist/output.css" rel="stylesheet" />
</head>

<body>
	<?php include '../includes/sidebar.php'; ?>

	<div class="ml-64 p-8">
		<div class="max-w-7xl mx-auto">
			<!-- Header -->
			<div class="flex justify-between items-center mb-8">
				<div>
					<h1 class="text-2xl font-bold text-gray-800">จัดการผลงาน</h1>
					<p class="text-gray-600 mt-1">จัดการผลงานทั้งหมดในระบบ</p>
				</div>
			</div>

			<!-- Results Count -->
			<div class="mb-6 text-sm text-gray-600 bg-indigo-50 p-3 rounded-lg">
				<i class="fas fa-image mr-2 text-indigo-600"></i>
				พบผลงานทั้งหมด
				<?= $result->num_rows ?> รายการ
			</div>

			<!-- Portfolio Grid -->
			<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
				<a href="portfolio_add.php" class="border-2 border-dashed border-gray-300 rounded-xl hover:border-indigo-400 transition duration-300 flex flex-col items-center justify-center p-8 bg-gray-50">
					<div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center text-indigo-600 mb-4">
						<i class="fas fa-plus"></i>
					</div>
					<h3 class="font-medium text-gray-700 mb-1">เพิ่มผลงานใหม่</h3>
					<p class="text-gray-500 text-sm text-center">จัดแสดงผลงานการออกแบบล่าสุดของคุณ</p>
				</a>
				<?php if (
					$result->num_rows > 0
				): ?>
					<?php while ($portfolio = $result->fetch_assoc()):
						$tags = json_decode($portfolio['tags'], true); // ตรวจสอบว่าภาพมีอยู่จริง
						$imagePath = __DIR__ . '/../../' . $portfolio['image_url'];
						$imageExists =
							file_exists($imagePath); ?>
						<div
							class="portfolio-item bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 relative">
							<div class="relative">
								<div class="w-full h-48 <?= $imageExists ? '' : '' ?>">
									<?php if ($imageExists): ?>
										<img src="/graphic-design/<?= htmlspecialchars($portfolio['image_url']) ?>"
											alt="<?= htmlspecialchars($portfolio['title']) ?>"
											class="w-full h-full object-cover" />

									<?php else: ?>
										<div
											class="w-full h-full flex items-center justify-center text-white font-bold text-2xl">
											<?= htmlspecialchars(substr($portfolio['title'], 0, 1)) ?>
										</div>
									<?php endif; ?>
								</div>

								<div class="absolute bottom-4 flex space-x-2">
									<a href="portfolio_edit.php?id=<?= $portfolio['portfolio_id'] ?>"
										class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-indigo-600 shadow-sm hover:bg-indigo-50 transition">
										<i class="fas fa-pencil-alt text-xs"></i>
									</a>
									<a href="portfolio_delete.php?id=<?= $portfolio['portfolio_id'] ?>"
										class="w-8 h-8 bg-white rounded-full flex items-center justify-center text-red-600 shadow-sm hover:bg-red-50 transition"
										onclick="return confirm('แน่ใจว่าต้องการลบผลงานนี้?')">
										<i class="fas fa-trash-alt text-xs"></i>
									</a>
								</div>

								<!-- Badge สถานะ -->
								<div class="absolute top-2 flex space-x-2">
									<?php if (!$portfolio['is_active']): ?>
										<span
											class="px-2 py-1 bg-red-100 text-red-800 text-xs rounded-full ml-2">
											<i class="fas fa-eye-slash mr-1"></i>
											ซ่อน
										</span>
									<?php endif; ?>
									<?php if ($portfolio['is_featured']): ?>
										<span
											class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded-full ml-2">
											<i class="fas fa-star mr-1"></i>
											แนะนำ
										</span>
									<?php endif; ?>
								</div>
							</div>

							<div class="p-4">
								<h3 class="font-medium text-gray-900">
									<?= htmlspecialchars($portfolio['title']) ?>
								</h3>

								<?php if ($portfolio['client_name']): ?>
									<p class="text-gray-500 text-sm mt-1">
										ลูกค้า:
										<?= htmlspecialchars($portfolio['client_name']) ?>
									</p>
								<?php endif; ?>

								<p class="text-gray-500 text-sm mt-1">
									บริการ:
									<?= htmlspecialchars($portfolio['service_name']) ?>
								</p>

								<!-- แท็ก -->
								<?php
								// ทำความสะอาดแท็ก: trim และตัดค่าว่างออก
								$cleanTags = [];
								if (is_array($tags)) {
									$cleanTags = array_values(array_filter(array_map('trim', $tags), function ($v) {
										return $v !== '';
									}));
								}
								?>
								<?php if (!empty($cleanTags)): ?>
									<div class="mt-3 flex flex-wrap gap-1">
										<?php foreach (array_slice($cleanTags, 0, 3) as $tag): ?>
											<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
												<?= htmlspecialchars($tag) ?>
											</span>
										<?php endforeach; ?>
										<?php if (count($cleanTags) > 3): ?>
											<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
												+
												<?= count($cleanTags) - 3 ?>
											</span>
										<?php endif; ?>
									</div>
								<?php endif; ?>

								<!-- วันที่สร้าง -->
								<div class="text-xs text-gray-400 mt-2">
									<i class="far fa-clock mr-1"></i>
									<?= date('d/m/Y', strtotime($portfolio['created_at'])) ?>
								</div>
							</div>
						</div>
					<?php endwhile; ?>
				<?php else: ?>
					<div class="col-span-full text-center py-12">
						<div class="text-gray-400 mb-4">
							<i class="fas fa-image fa-3x"></i>
						</div>
						<h3 class="text-lg font-medium text-gray-600 mb-2">ยังไม่มีผลงาน</h3>
						<p class="text-gray-500 mb-4">เริ่มต้นโดยการเพิ่มผลงานแรกของคุณ</p>
						<a href="portfolio_add.php"
							class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition inline-flex items-center">
							<i class="fas fa-plus mr-2"></i>
							เพิ่มผลงานใหม่
						</a>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<script>
		// Fallback for images that fail to load
		document.addEventListener('DOMContentLoaded', function() {
			document.querySelectorAll('img').forEach((img) => {
				img.addEventListener('error', function() {
					this.style.display = 'none';
					const parent = this.parentElement;
					parent.classList.add('fallback-image');
					const title = this.alt || 'P';
					parent.innerHTML =
						'<div class="w-full h-full flex items-center justify-center text-white font-bold text-2xl">' +
						title.charAt(0).toUpperCase() +
						'</div>';
				});
			});
		});
	</script>
</body>

</html>