fetch('https://v2.web.ve/bcv/')
.then((response) => response.json())
.then((data) => {
    const element = document.getElementById('tasa_bcv');
    element.innerHTML = data.price;
    //console.log(data);
});
