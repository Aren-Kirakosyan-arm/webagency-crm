document.addEventListener('DOMContentLoaded',()=>{
 const modal=document.getElementById('projectModal');
 const openButtons=['newProjectBtn','newProjectBtn2','emptyNewProjectBtn'].map(id=>document.getElementById(id)).filter(Boolean);
 const closeButtons=['closeModal','cancelModal'].map(id=>document.getElementById(id)).filter(Boolean);
 const open=()=>{if(!modal)return;modal.classList.add('visible');modal.setAttribute('aria-hidden','false');modal.querySelector('[name="project_name"]')?.focus();};
 const close=()=>{if(!modal)return;modal.classList.remove('visible');modal.setAttribute('aria-hidden','true');};
 openButtons.forEach(b=>b.addEventListener('click',open));closeButtons.forEach(b=>b.addEventListener('click',close));
 modal?.addEventListener('click',e=>{if(e.target===modal)close();});
 document.addEventListener('keydown',e=>{if(e.key==='Escape')close();});
 const menu=document.getElementById('menuToggle'),sidebar=document.getElementById('sidebar');
 if(menu&&sidebar)menu.addEventListener('click',()=>sidebar.classList.toggle('sidebar-open'));
 const canvas=document.getElementById('projectsChart');
 if(canvas&&window.Chart&&window.crmChartData)new Chart(canvas,{type:'doughnut',data:{labels:['Planning','In progress','Completed'],datasets:[{data:[window.crmChartData.planning,window.crmChartData.in_progress,window.crmChartData.completed],backgroundColor:['#a5b4fc','#6675f5','#34c99a'],borderWidth:0}]},options:{responsive:true,maintainAspectRatio:false,cutout:'70%',plugins:{legend:{display:false}}}});
});
