document.addEventListener('DOMContentLoaded', function () {
    const addModal = document.getElementById('addShippingModal');
    const editModal = document.getElementById('editShippingModal');
    const addShippingButton = document.getElementById('addShippingButton');
    const closeModalButton = document.getElementById('closeModal');
    const closeEditModalButton = document.getElementById('closeEditModal');
    const cancelButton = document.getElementById('cancelAddButton');
     const cancelEditButton = document.getElementById('cancelEditButton');
     const editButtons = document.querySelectorAll('.btn-update');


     addShippingButton.addEventListener('click', function() {
          addModal.style.display = 'block';
        });
  closeModalButton.addEventListener('click', function() {
          addModal.style.display = 'none';
        });
    closeEditModalButton.addEventListener('click', function() {
          editModal.style.display = 'none';
        });
       cancelButton.addEventListener('click', function() {
         addModal.style.display = 'none';
       });
       cancelEditButton.addEventListener('click', function() {
         editModal.style.display = 'none';
       });
       window.addEventListener('click', function(event) {
           if (event.target == addModal) {
                addModal.style.display = "none";
            }
           if (event.target == editModal) {
                editModal.style.display = "none";
            }
    });
   editButtons.forEach(button => {
        button.addEventListener('click', function() {
             editModal.style.display = 'block';
            const id = this.getAttribute('data-id');
             const name = this.getAttribute('data-name');
             const price = this.getAttribute('data-price');
             document.getElementById('editmadonvi').value = id;
             document.getElementById('edittendonvi').value = name;
              document.getElementById('editgiavanchuyen').value = price;
         })
   })
});