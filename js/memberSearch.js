document.addEventListener('DOMContentLoaded', function () {

    const searchInput = document.getElementById('memberSearchInput');
    const rows = document.querySelectorAll('#projectMembersList .member-row');

    if (!searchInput) return;

    let timer;

    searchInput.addEventListener('input', function () {

        clearTimeout(timer);

        timer = setTimeout(() => {

            const filter = this.value.trim().toLowerCase();

            rows.forEach(row => {

                const nameCell = row.querySelector('.member-name');
                if (!nameCell) return;

                const name = nameCell.textContent.toLowerCase();

                if (name.includes(filter)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }

            });

        }, 200); // جلوگیری از اجرای زیاد هنگام تایپ سریع

    });

});
