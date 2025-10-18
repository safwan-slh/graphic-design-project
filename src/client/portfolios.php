<?php
require __DIR__ . '/../includes/db_connect.php';

// ดึงข้อมูลผลงานทั้งหมด
$sql = "SELECT p.*, s.service_name FROM portfolios p
                  LEFT JOIN services s ON p.service_id = s.service_id
                  WHERE p.is_active = 1
                  ORDER BY p.created_at DESC";

$portfolioResult = $conn->query($sql); ?>

<!DOCTYPE html>
<html lang="th">

<head>
	<meta charset="UTF-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<title>ผลงานร้าน</title>
	<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
	<link href="../../dist/output.css" rel="stylesheet" />
	<style>
		.font-thai {
			font-family: 'IBM Plex Sans Thai', sans-serif;
		}
	</style>
</head>

<body class="bg-gray-50 mt-10 font-thai">
	<?php include '../includes/navbar.php'; ?>
	<!-- Hero Section -->
	<div class="px-10 pt-10 mb-10">
		<div class="py-5 text-zinc-900 bg-white rounded-2xl p-2 border border-slate-200">
			<div class="container mx-auto px-4 pt-5 text-center">
				<div class="inline-block bg-yellow-100 text-yellow-800 px-4 py-2 rounded-full text-sm font-medium mb-4">
                        🎯 ผลงานโดดเด่น
                    </div>
				<h1 class="text-3xl md:text-5xl font-bold mb-4">ผลงานการออกแบบของเรา</h1>
				<p class="text-lg text-slate-600 mb-8">
					สำรวจผลงานการออกแบบกราฟิกที่เราได้สร้างให้กับลูกค้าทั้งในและต่างประเทศ ด้วยความคิดสร้างสรรค์และความเชี่ยวชาญ
				</p>
			</div>
		</div>
	</div>

	<div class="">
		<div class="container mx-auto px-4 py-10">
			<!-- Portfolio Grid -->
			<div class="grid grid-cols-3 gap-6 p-4">
				<?php if (
					$portfolioResult->num_rows > 0
				): ?>
					<?php while ($portfolio = $portfolioResult->fetch_assoc()):
						$tags = json_decode($portfolio['tags'], true); // ตรวจสอบว่าภาพมีอยู่จริง
						$imagePath = __DIR__ . '/../../' . $portfolio['image_url'];
						$imageExists =
							file_exists($imagePath); ?>
						<div
							class="portfolio-item bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100 relative">
							<div class="relative">
								<div class="w-full h-64 <?= $imageExists ? '' : '' ?>">
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
								<!-- Badge สถานะ -->
								<div class="absolute top-2 flex space-x-2">
									<?php if ($portfolio['is_featured']): ?>
										<span
											class="px-2 py-1 glassmorphism text-white text-xs font-bold rounded-full ml-2">
											<i class="fas fa-star mr-1 text-yellow-300"></i>
											แนะนำ
										</span>
									<?php endif; ?>
								</div>
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
									<div class="absolute bottom-2 left-2 flex items-center justify-between">
										<div class="flex flex-wrap gap-1">
											<?php foreach (array_slice($cleanTags, 0, 4) as $tag): ?>
												<span
													class=" glassmorphism text-white text-xs px-3 py-1 rounded-full">
													<?= htmlspecialchars($tag) ?>
												</span>
											<?php endforeach; ?>
											<?php if (count($cleanTags) > 4): ?>
												<span
													class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium glassmorphism text-white">
													+
													<?= count($cleanTags) - 4 ?>
												</span>
											<?php endif; ?>
										</div>
									</div>
								<?php endif; ?>
							</div>

							<div class="flex items-end p-6">
								<div class="">
									<h3 class="font-semibold text-lg text-gray-800 mb-2">
										<?= htmlspecialchars($portfolio['title']) ?></h3>
									<p class="text-gray-500 mb-4 text-md">
										<?= htmlspecialchars($portfolio['description']) ?></p>
									<p class="text-sm inline-flex items-center">
										<span
											class="w-2 h-2 bg-green-400 rounded-full mr-2"></span>
										บริการ:
										<?= htmlspecialchars($portfolio['service_name']) ?>
									</p>
								</div>
							</div>
							<!-- วันที่สร้าง
                                    <div class="flex items-center text-sm text-gray-500 mb-4">
                                        <i class="far fa-clock mr-1"></i>
                                        <?= date('d/m/Y', strtotime($portfolio['created_at'])) ?>
                                    </div> -->
						</div>
					<?php endwhile; ?>
				<?php else: ?>
					<div class="col-span-full text-center py-12">
						<div class="text-gray-400 mb-4">
							<i class="fas fa-image fa-3x"></i>
						</div>
						<h3 class="text-lg font-medium text-gray-600 mb-2">ยังไม่มีผลงาน</h3>
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