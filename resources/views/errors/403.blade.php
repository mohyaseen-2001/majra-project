@include('errors.weather', [
    'status' => 403,
    'arTitle' => 'الوصول ممنوع',
    'arMessage' => 'حسابك ما عنده الصلاحية المطلوبة لفتح هاي الصفحة.',
    'enTitle' => 'Access denied',
    'enMessage' => 'Your account doesn’t have permission to open this page.',
])
