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
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Thai:wght@300;400;500;600;700&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
	<style>
		.font-thai {
			font-family: 'IBM Plex Sans Thai', sans-serif;
		}
	</style>
</head>

<body class="mt-10 font-thai absolute inset-0 -z-10 h-full w-full bg-white bg-[linear-gradient(to_right,#8080800a_1px,transparent_1px),linear-gradient(to_bottom,#8080800a_1px,transparent_1px)] bg-[size:14px_24px]">
	<?php include '../includes/navbar.php'; ?>
	<!-- Hero Section -->
	<div class="px-10 pt-10 mb-10">
		<div class="py-5 text-zinc-900 rounded-2xl p-2  h-full w-full ">
			<div class="container mx-auto px-4 pt-5 text-center">
				<h1 class="text-3xl md:text-5xl font-bold mb-4">ผลงานการออกแบบของเรา</h1>
				<p class="text-lg text-slate-600 mb-8">
					สำรวจผลงานการออกแบบกราฟิกที่เราได้สร้างให้กับลูกค้าทั้งในและต่างประเทศ ด้วยความคิดสร้างสรรค์และความเชี่ยวชาญ
				</p>
			</div>
		</div>
	</div>

	<div class="mb-10 pb-10">
		<div class="container mx-auto px-4 ">
			<!-- Portfolio Grid -->
			<div class="columns-1 sm:columns-2 md:columns-3 gap-4 [column-fill:_balance]">
				<?php
				$i = 0;
				if ($portfolioResult->num_rows > 0):
					while ($portfolio = $portfolioResult->fetch_assoc()):
						$tags = json_decode($portfolio['tags'], true);
						$imagePath = __DIR__ . '/../../' . $portfolio['image_url'];
						$imageExists = file_exists($imagePath);
						$isFeatured = $portfolio['is_featured'];
						// Featured item: col-span-2 เฉพาะตัวแรกที่เป็น featured
						$itemClass = $isFeatured && $i === 0
							? ''
							: '';
				?>
						<div
							class="portfolio-item mb-4 break-inside-avoid p-1.5 bg-white rounded-2xl shadow-md hover:shadow-lg ring-1 ring-gray-200 transition relative <?= $itemClass ?>">
							<div class="w-full relative">
								<?php if ($imageExists): ?>
									<img src="/graphic-design/<?= htmlspecialchars($portfolio['image_url']) ?>"
										alt="<?= htmlspecialchars($portfolio['title']) ?>"
										class="w-full rounded-xl object-cover cursor-pointer transition ring-1 ring-gray-200 shadow-sm hover:brightness-90"
										onclick="openPortfolioModal(
        								<?= htmlspecialchars(json_encode([
											'title' => $portfolio['title'],
											'description' => $portfolio['description'],
											'image_url' => '/graphic-design/' . $portfolio['image_url'],
											'service_name' => $portfolio['service_name'],
											'tags' => $cleanTags,
											'is_featured' => $portfolio['is_featured'],
										])) ?>
    								)" />
								<?php else: ?>
									<div
										class="w-full flex items-center justify-center text-white font-bold text-2xl">
										<?= htmlspecialchars(substr($portfolio['title'], 0, 1)) ?>
									</div>
								<?php endif; ?>
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
								<div class="absolute top-2 right-2 flex space-x-2">
									<span class="px-2 py-1 glassmorphism text-white text-xs rounded-full shadow-md ml-2">
										บริการ: <?= htmlspecialchars($portfolio['service_name']) ?>
									</span>
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
	<?php
    include __DIR__ . '/../includes/footer.php';
    ?>

	<!-- Image Modal -->
	<div id="imgModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70 backdrop-blur-md hidden">
		<div class="relative">
			<button onclick="closeImgModal()" class="absolute -top-4 -right-4 text-zinc-900 bg-white rounded-full p-2 shadow-md transition-all duration-300 ease-in-out hover:scale-105">
				<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
					<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
				</svg>
			</button>
			<img id="imgModalPic" src="" class="w-full rounded-xl object-cover max-h-80 mb-4" alt="รีวิว">
			<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-2 w-full">
				<div id="imgModalDetail" class="bg-white rounded-xl p-2 ring-1 ring-gray-200"></div>
			</div>
		</div>
	</div>

	<script>
		function openPortfolioModal(data) {
			// รูป
			document.getElementById('imgModalPic').src = data.image_url || '';

			// Fallback สำหรับข้อมูลที่เป็น null หรือ undefined
			const title = data.title || '-';
			const description = data.description || '-';
			const service_name = data.service_name || '-';
			const tags = Array.isArray(data.tags) ? data.tags : [];
			const is_featured = data.is_featured ? true : false;

			// รายละเอียด
			let tagsHtml = '';
			if (tags.length) {
				tagsHtml = '<div class="flex flex-wrap gap-2 mb-2">';
				tags.forEach(tag => {
					if (tag) {
						tagsHtml += `<span class="bg-zinc-900 text-white text-xs px-3 py-1 rounded-full">${tag}</span>`;
					}
				});
				tagsHtml += '</div>';
			}
			document.getElementById('imgModalDetail').innerHTML = `
        ${is_featured ? `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 border border-yellow-300 mb-4"><i class="fas fa-star mr-1"></i> แนะนำ</span>` : ''}
        <h2 class="text-xl font-bold text-zinc-900 mb-2">${title}</h2>
        <p class="text-gray-500 mb-2">${description}</p>
        <p class="text-sm mb-2"><span class="w-2 h-2 bg-green-400 rounded-full inline-block mr-2"></span>บริการ: ${service_name}</p>
        ${tagsHtml}
    `;

			document.getElementById('imgModal').classList.remove('hidden');
			document.body.classList.add('modal-open');
		}

		function closeImgModal() {
			document.getElementById('imgModal').classList.add('hidden');
			document.body.classList.remove('modal-open');
		}

		function openImgModal(src) {
			const img = document.getElementById('imgModalPic');
			img.src = src;
			document.getElementById('imgModal').classList.remove('hidden');
			document.body.classList.add('modal-open');
		}
		// ปิด modal เมื่อคลิกพื้นหลัง
		document.getElementById('imgModal').addEventListener('click', function(e) {
			if (e.target === this) closeImgModal();
		});
	</script>

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