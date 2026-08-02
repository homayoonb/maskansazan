
<style>
    #lang-select {
        background-color: black;
        color: white;
    }

    #lang-select option {
        background-color: black;
        color: white;
    }

</style>
<div id="datetime-box" style="
    color: black;
    font-family: 'Tahoma', sans-serif;
    font-size: 14px;
    font-weight: bold;
    padding: 8px 12px;
    border-radius: 8px;
    display: inline-flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
">
    <span id="persian-date"></span>
    <span id="gregorian-date"></span>
    <span id="local-time"></span>

    <select id="lang-select" style="
        background: rgba(255,255,255,0.2);
        color: black;
        background-color: white;
        border: none;
        border-radius: 5px;
        padding: 3px 6px;
        font-size: 14px;
        cursor: pointer;
    ">
        <option value="fa" style="color: black; background: white;">فارسی</option>
        <option value="en" style="color: black; background: white;">English</option>
    </select>
</div>


<script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/moment-jalaali@0.9.2/build/moment-jalaali.js"></script>




<script>
    let selectedLang = 'fa';
    document.getElementById('lang-select').addEventListener('change', function() {
        selectedLang = this.value;
    });

    const persianWeekDays = ['یکشنبه','دوشنبه','سه‌شنبه','چهارشنبه','پنج‌شنبه','جمعه','شنبه'];
    const englishWeekDays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

    function toPersianNumber(input) {
        const persianNums = ['۰','۱','۲','۳','۴','۵','۶','۷','۸','۹'];
        return input.replace(/\d/g, d => persianNums[d]);
    }

    function updateDateTime() {
        const now = new Date();

        // تاریخ شمسی
        let persianDate = moment(now).locale('fa').format('jYYYY/jMM/jDD');
        let persianWeekDay = persianWeekDays[now.getDay()]; // روز هفته شمسی به فارسی

        // تاریخ میلادی
        let gregorianDate = now.toLocaleDateString(
            selectedLang==='fa' ? 'fa-IR' : 'en-US',
            { year:'numeric', month:'long', day:'numeric' }
        );
        let gregorianWeekDay = selectedLang==='fa' ? persianWeekDays[now.getDay()] : englishWeekDays[now.getDay()];

        // زمان
        let localTime = now.toLocaleTimeString(
            selectedLang==='fa' ? 'fa-IR' : 'en-US',
            { hour:'2-digit', minute:'2-digit', second:'2-digit' }
        );

        document.getElementById('persian-date').textContent = "📅 شمسی: " + (selectedLang==='fa' ? toPersianNumber(persianWeekDay + ' ' + persianDate) : persianDate);
        document.getElementById('local-time').textContent = "🕒 زمان: " + (selectedLang==='fa' ? toPersianNumber(localTime) : localTime);
        document.getElementById('persian-date').textContent =
            "📅 " + (selectedLang==='fa' ? toPersianNumber(persianWeekDay + ' ' + persianDate) : persianDate);

        document.getElementById('local-time').textContent =
            "🕒 " + (selectedLang==='fa' ? toPersianNumber(localTime) : localTime);

        setTimeout(updateDateTime, 1000);

    }

    updateDateTime();
</script>


<style>
    #datetime-box{
        color:black;
        font-family:'Tahoma', sans-serif;
        font-size:14px;
        font-weight:bold;
        padding:8px 12px;
        border-radius:8px;

        display:flex;
        flex-wrap:wrap;        /* اجازه شکستن خطوط */
        align-items:center;
        gap:8px;

        max-width:100%;        /* جلوگیری از بیرون زدن */
    }

    #datetime-box span{
        white-space:nowrap;
    }

    #datetime-box select{
        background:rgba(255,255,255,0.2);
        color:black;
        border:none;
        border-radius:5px;
        padding:3px 6px;
        font-size:13px;
        cursor:pointer;
    }

    /* موبایل */
    @media (max-width:768px){

        #datetime-box{
            font-size:12px;
            gap:5px;
            padding:6px 8px;
            width:100%;
            justify-content:flex-start;
        }

        #datetime-box span{
            font-size:11px;
        }

        #datetime-box select{
            font-size:11px;
            padding:2px 5px;
        }

    }


</style>
