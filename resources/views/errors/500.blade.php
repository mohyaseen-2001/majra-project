@include('errors.weather', [
    'status' => 500,
    'arTitle' => 'صار خطأ في الموقع',
    'arMessage' => 'ما قدرنا نكمل طلبك بسبب مشكلة في الموقع. جرّب مرة ثانية بعد شوي.',
    'enTitle' => 'Something went wrong',
    'enMessage' => 'A server error prevented us from completing your request. Try again in a moment.',
])
