document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const scoreDisplay = document.getElementById('score-display');
    const phqRadios = form.querySelectorAll('input[type="radio"][name^="c"]');

    function calculateScore() {
        let totalScore = 0;
        for (let i = 1; i <= 9; i++) {
            const radio = form.querySelector(`input[name="c${i}"]:checked`);
            if (radio) {
                totalScore += parseInt(radio.value, 10);
            }
        }
        scoreDisplay.textContent = totalScore;
    }

    phqRadios.forEach(radio => {
        // ตรวจสอบเฉพาะ c1-c9
        if (parseInt(radio.name.substring(1)) <= 9) {
            radio.addEventListener('change', calculateScore);
        }
    });

    // คำนวณคะแนนครั้งแรกเมื่อโหลดหน้า
    calculateScore();
});