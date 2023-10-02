// ------- scroll --------//

document.querySelector('.scroll').addEventListener('wheel', (e) => {
    const delta = e.deltaY || -e.wheelDelta || e.detail;

    const element = document.querySelector('.content-body');
    element.scrollTop += delta;

    e.preventDefault();
});