document.addEventListener('submit', event => {
 const text = event.target.dataset.confirm;
 if (text && !window.confirm(text)) event.preventDefault();
});
document.addEventListener('click', event => {
 if (event.target.closest('[data-print]')) window.print();
});
