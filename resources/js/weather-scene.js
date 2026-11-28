const scene = document.createElement('div');
scene.className = 'weather-scene';
scene.setAttribute('aria-hidden', 'true');
scene.setAttribute('translate', 'no');
scene.innerHTML = `
    <span class="weather-sky"></span>
    <span class="weather-rain"></span>
    <span class="weather-lightning"></span>
    <span class="weather-water"></span>
`;
document.body.prepend(scene);
