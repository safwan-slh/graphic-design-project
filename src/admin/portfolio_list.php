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
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap"
		rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
	<style>
		.font-thai {
			font-family: 'IBM Plex Sans Thai', sans-serif;
		}
	</style>
</head>

<body class="font-thai bg-zinc-100">
	<?php include '../includes/sidebar.php'; ?>

	<div class="ml-64">
		<!-- breadcrumb -->
		<?php
		$breadcrumb = ['Dashboard', 'จัดการผลงาน'];
		$breadcrumb_links = ['/graphic-design/src/admin/index.php'];
		include '../includes/admin_navbar.php';
		?>
		<!-- Main Content -->
		<div class="p-6">
			<!-- Header -->
			<div class=" text-zinc-900 bg-white rounded-2xl border border-slate-200 mb-2">
				<!-- Header -->
				<div class="flex items-center p-4">
					<div class="mr-4 rounded-xl bg-zinc-900 p-3">
						<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-6 w-6 text-white">
							<path d="M3.375 3C2.339 3 1.5 3.84 1.5 4.875v.75c0 1.036.84 1.875 1.875 1.875h17.25c1.035 0 1.875-.84 1.875-1.875v-.75C22.5 3.839 21.66 3 20.625 3H3.375Z" />
							<path fill-rule="evenodd" d="m3.087 9 .54 9.176A3 3 0 0 0 6.62 21h10.757a3 3 0 0 0 2.995-2.824L20.913 9H3.087Zm6.163 3.75A.75.75 0 0 1 10 12h4a.75.75 0 0 1 0 1.5h-4a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" />
						</svg>
					</div>
					<div class="">
						<h1 class="flex items-center text-2xl font-bold text-zinc-900">
							จัดการผลงาน
						</h1>
						<p class="text-gray-600">
							จัดการผลงานทั้งหมดในระบบ
						</p>
					</div>
				</div>
			</div>
			<div class="max-w-7xl mx-auto">

				<!-- Portfolio Grid -->
				<div class="grid grid-cols-3 gap-6 bg-white rounded-2xl shadow-sm p-4 space-y-2 cursor-pointer ring-1 ring-gray-200">
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

									<div class="absolute bottom-2 flex space-x-2 ml-2">
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

								<div class="p-2">
									<div class="p-3 rounded-xl ring-1 ring-gray-200">
										<h3 class="text-zinc-800 text-sm">
											<span class="text-zinc-500">ชื่อผลงาน:</span>
											<?= htmlspecialchars($portfolio['title']) ?>
										</h3>
										<p class="text-zinc-800 text-sm mt-1">
											<span class="text-zinc-500">รายละเอียด:</span>
											<?= nl2br(htmlspecialchars($portfolio['description'])) ?>
										</p>
										<?php if ($portfolio['client_name']): ?>
											<p class="text-zinc-800 text-sm mt-1">
												<span class="text-zinc-500">ลูกค้า:</span>
												<?= htmlspecialchars($portfolio['client_name']) ?>
											</p>
										<?php else: ?>
											<p class="text-zinc-800 text-sm mt-1">
												<span class="text-zinc-500">ลูกค้า:</span>
												ไม่ระบุ
											</p>
										<?php endif; ?>
										<p class="text-zinc-800 text-sm mt-1">
											<span class="text-zinc-500">บริการ:</span>
											<?= htmlspecialchars($portfolio['service_name']) ?>
										</p>
									</div>
								</div>
								<div class="border-t border-gray-200 px-5 py-2 bg-gray-50 flex justify-between items-center">
									<div class="text-zinc-600 text-sm">
										<i class="far fa-clock mr-1"></i>
										<?= date('d/m/Y', strtotime($portfolio['created_at'])) ?>
									</div>
									<div class="flex justify-center">
										<button id="dropdownDefaultButton<?= $portfolio['portfolio_id'] ?>" data-dropdown-toggle="cancel<?= $portfolio['portfolio_id'] ?>" class="flex items-center p-0.5 text-sm font-medium text-center text-gray-400 rounded-lg hover:bg-gray-100 focus:ring-4 focus:outline-none focus:ring-gray-200" type="button">
											<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="size-6">
												<path fill-rule="evenodd" d="M10.5 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Zm0 6a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0Z" clip-rule="evenodd" />
											</svg>
										</button>
										<div id="cancel<?= $portfolio['portfolio_id'] ?>" class="z-10 hidden bg-white divide-y rounded-xl shadow-md w-44 border-1.5 border-gray-200 ring-1 ring-gray-200">
											<ul class="space-y-2 p-2 py-2 text-sm text-gray-700" aria-labelledby="dropdownDefaultButton">
												<li>
													<a href="portfolio_edit.php?id=<?= $portfolio['portfolio_id'] ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-blue-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
														แก้ไข
														<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
															<path d="M21.731 2.269a2.625 2.625 0 0 0-3.712 0l-1.157 1.157 3.712 3.712 1.157-1.157a2.625 2.625 0 0 0 0-3.712ZM19.513 8.199l-3.712-3.712-12.15 12.15a5.25 5.25 0 0 0-1.32 2.214l-.8 2.685a.75.75 0 0 0 .933.933l2.685-.8a5.25 5.25 0 0 0 2.214-1.32L19.513 8.2Z" />
														</svg>
													</a>
												</li>
												<li>
													<a href="portfolio_delete.php?id=<?= $portfolio['portfolio_id'] ?>" class=" flex items-center justify-between px-3 py-2 text-sm rounded-lg bg-zinc-50 text-red-600 hover:bg-zinc-100 transition-colors duration-200 ring-1 ring-gray-200">
														ลบ
														<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
															<path fill-rule="evenodd" d="M16.5 4.478v.227a48.816 48.816 0 0 1 3.878.512.75.75 0 1 1-.256 1.478l-.209-.035-1.005 13.07a3 3 0 0 1-2.991 2.77H8.084a3 3 0 0 1-2.991-2.77L4.087 6.66l-.209.035a.75.75 0 0 1-.256-1.478A48.567 48.567 0 0 1 7.5 4.705v-.227c0-1.564 1.213-2.9 2.816-2.951a52.662 52.662 0 0 1 3.369 0c1.603.051 2.815 1.387 2.815 2.951Zm-6.136-1.452a51.196 51.196 0 0 1 3.273 0C14.39 3.05 15 3.684 15 4.478v.113a49.488 49.488 0 0 0-6 0v-.113c0-.794.609-1.428 1.364-1.452Zm-.355 5.945a.75.75 0 1 0-1.5.058l.347 9a.75.75 0 1 0 1.499-.058l-.346-9Zm5.48.058a.75.75 0 1 0-1.498-.058l-.347 9a.75.75 0 0 0 1.5.058l.345-9Z" clip-rule="evenodd" />
														</svg>
													</a>
												</li>
											</ul>
										</div>
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
	</div>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.js"></script>
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