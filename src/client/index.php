<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.7.0/flowbite.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="../../dist/output.css" rel="stylesheet" />
</head>

<body>
    <?php
    include __DIR__ . '/../includes/navbar.php';
    ?>
    <section class="flex-1 flex items-center mt-24 pt-24 pb-16 px-6">
        <div class="max-w-6xl mx-auto grid md:grid-cols-2 gap-12 items-center">
            <div>
                <h1 class="font-display text-4xl md:text-6xl font-bold leading-tight mb-6">
                    <span class="gradient-text">การออกแบบ</span><br>
                    djncas
                </h1>
                <p class="text-lg text-gray-600 mb-8 max-w-lg">
                    เราสร้างประสบการณ์ผู้ใช้ที่ไร้รอยต่อ ด้วยแนวคิดแบบมินิมอลที่เน้นการทำงานจริง
                </p>
                <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
                    <a href="#contact" class="bg-zinc-900 text-white px-8 py-3 rounded-full font-medium hover:shadow-lg transition-all">
                        เริ่มต้นโครงการ
                    </a>
                    <a href="#works" class="border border-gray-300 text-gray-700 px-8 py-3 rounded-full font-medium hover:bg-gray-50 transition-colors">
                        ดูผลงาน
                    </a>
                </div>
            </div>
            <div class="relative">
                <div class="absolute -top-8 -right-8 w-64 h-64 bg-purple-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"></div>
                <div class="absolute -bottom-8 -left-8 w-64 h-64 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"></div>
            </div>
        </div>
    </section>
</body>

</html>