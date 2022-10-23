fetch('https://exchangemonitor.net/ajax/widget-unique?country=ve&type=bcv')
.then((response) => response.json())
.then((data) => {
    const element = document.getElementById('tasa_bcv');
    element.innerHTML = data.price;
    //console.log(data);
});
