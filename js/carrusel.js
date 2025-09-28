document.addEventListener('DOMContentLoaded', ()=> {
  const wrapper = document.querySelector('.promo-wrapper');
  const promos = Array.from(document.querySelectorAll('.promo'));
  const prev = document.querySelector('#carrusel .prev');
  const next = document.querySelector('#carrusel .next');
  if(!wrapper || promos.length === 0) {
    if(prev) prev.style.display = 'none';
    if(next) next.style.display = 'none';
    return;
  }

  let index = 0;
  let timer = null;

  function update(){
    wrapper.style.transform = `translateX(${-index * 100}%)`;
    if(promos.length <= 1){
      prev && (prev.style.display = 'none');
      next && (next.style.display = 'none');
    } else {
      prev && (prev.style.display = '');
      next && (next.style.display = '');
    }
  }

  function nextSlide(){ index = (index + 1) % promos.length; update(); }
  function prevSlide(){ index = (index - 1 + promos.length) % promos.length; update(); }

  next && next.addEventListener('click', ()=> { nextSlide(); restartAuto(); });
  prev && prev.addEventListener('click', ()=> { prevSlide(); restartAuto(); });

  function startAuto(){ if(timer) clearInterval(timer); timer = setInterval(()=> nextSlide(), 4500); }
  function stopAuto(){ if(timer) clearInterval(timer); timer = null; }
  function restartAuto(){ stopAuto(); startAuto(); }

  wrapper.addEventListener('mouseenter', stopAuto);
  wrapper.addEventListener('mouseleave', startAuto);

  document.addEventListener('keydown', (e)=>{
    if(document.activeElement && ['INPUT','TEXTAREA'].includes(document.activeElement.tagName)) return;
    if(e.key === 'ArrowLeft') prevSlide();
    if(e.key === 'ArrowRight') nextSlide();
  });

  setTimeout(()=> { update(); startAuto(); }, 60);

  Array.from(wrapper.querySelectorAll('img')).forEach(img=>{
    if(!img.complete) img.addEventListener('load', ()=> setTimeout(update,30));
  });
});
