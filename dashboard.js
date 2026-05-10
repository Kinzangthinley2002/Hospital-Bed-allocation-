$(document).ready(function(){

function showMessage(message,type='success'){
  const alertHtml = `<div class="alert alert-${type} alert-dismissible fade show alert-position" role="alert">
                        ${message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>`;
  $('body').append(alertHtml);
  setTimeout(()=>{$('.alert').alert('close');},4000);
}

function fetchAndRender(){
  $.getJSON('fetch_wards_patients.php',function(data){
    const searchText = $('#searchPatient').val().toLowerCase();
    const filterPriority = $('#filterPriority').val();
    let html='';
    data.wards.forEach(ward=>{
      const occupancyPercent = Math.round((ward.used/ward.capacity)*100);
      html+=`<div class="card p-3" style="width:220px;">
        <h6>${ward.name} (${ward.used}/${ward.capacity})</h6>
        <div class="progress mb-2" style="height:18px;">
          <div class="progress-bar ${occupancyPercent<70?'bg-success':occupancyPercent<100?'bg-warning':'bg-danger'}" style="width:${occupancyPercent}%;">
            ${ward.used}/${ward.capacity}
          </div>
        </div>
        <div class="beds" data-ward-id="${ward.id}" style="min-height:140px;">`;

      for(let i=1;i<=ward.capacity;i++){
        let bed = ward.allocations[i]||null;
        let bedClass = bed?'':'empty';
        if(bed){
          if(searchText && !bed.name.toLowerCase().includes(searchText)) continue;
          if(filterPriority && bed.priority!==filterPriority) continue;
        }
        let bedContent = bed?
          `<div class="patient-avatar ${bed.priority.toLowerCase()}" draggable="true" data-patient-id="${bed.id}" 
            title="Name: ${bed.name}\nPriority: ${bed.priority}\nAge: ${bed.age}\nGender: ${bed.gender}">
            ${bed.name.split(' ').map(n=>n[0]).join('')}
          </div>`:'Empty';
        html+=`<div class="bed ${bedClass} mb-1 p-1">${bedContent}</div>`;
      }

      html+=`</div></div>`;
    });

    $('#wardsContainer').html(html);
    const tooltipList=[].slice.call(document.querySelectorAll('.patient-avatar'));
    tooltipList.map(el=>new bootstrap.Tooltip(el,{placement:'top'}));
    enableDragDrop();
  });
}

function enableDragDrop(){
  let dragged=null;
  $('.patient-avatar').on('dragstart',function(){dragged=this;});
  $('.bed').on('dragover',function(e){e.preventDefault(); $(this).addClass('border border-primary');});
  $('.bed').on('dragleave',function(){$(this).removeClass('border border-primary');});
  $('.bed').on('drop',function(){
    $(this).removeClass('border border-primary');
    if(!dragged) return;
    let patientId = $(dragged).data('patient-id');
    let wardId = $(this).closest('.beds').data('ward-id');
    let bedNumber = $(this).index()+1;
    $.post('manual_allocate.php',{patient_id:patientId,ward_id:wardId,bed_number:bedNumber},function(res){
      res=JSON.parse(res);
      showMessage(res.message,res.status);
      fetchAndRender();
    });
  });
}

// Forms
$('#wardForm').submit(function(e){
  e.preventDefault();
  $.post('add_ward.php',$(this).serialize(),function(res){
    res=JSON.parse(res);
    showMessage(res.message,res.status);
    $('#wardForm')[0].reset();
    fetchAndRender();
  });
});

$('#patientForm').submit(function(e){
  e.preventDefault();
  $.post('add_patient.php',$(this).serialize(),function(res){
    res=JSON.parse(res);
    showMessage(res.message,res.status);
    $('#patientForm')[0].reset();
    fetchAndRender();
  });
});

// Graph coloring allocation
$('#allocateBtn').click(function(){
  $.getJSON('allocate.php',function(res){
    showMessage(res.message,res.status);
    fetchAndRender();
  });
});

// Reallocate all patients
$('#reallocateBtn').click(function(){
  $.getJSON('reallocate.php',function(res){
    showMessage(res.message,res.status);
    fetchAndRender();
  });
});

// Search & filter
$('#searchPatient,#filterPriority').on('input change', fetchAndRender);
fetchAndRender();

});
