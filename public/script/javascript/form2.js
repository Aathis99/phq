document.addEventListener("DOMContentLoaded", () => {
  // 2. Populate Dropdowns

  // Age (12-20)
  const ageSelect = document.getElementById("age");
  for (let i = 12; i <= 20; i++) {
    const option = document.createElement("option");
    option.value = i;
    option.textContent = `${i} ปี`;
    ageSelect.appendChild(option);
  }

  // Education Level (M.1 - M.6)
  const eduLevelSelect = document.getElementById("edu_level");
  for (let i = 1; i <= 6; i++) {
    const option = document.createElement("option");
    option.value = `ม.${i}`;
    option.textContent = `มัธยมศึกษาปีที่ ${i}`;
    eduLevelSelect.appendChild(option);
  }

  // Classroom (1 - 20)
  const eduRoomSelect = document.getElementById("edu_room");
  for (let i = 1; i <= 20; i++) {
    const option = document.createElement("option");
    option.value = i;
    option.textContent = ` ${i}`;
    eduRoomSelect.appendChild(option);
  }

  // 3. Date Handling
  const today = new Date();

  // Format YYYY-MM-DD for input type="date"
  const yyyy = today.getFullYear();
  const mm = String(today.getMonth() + 1).padStart(2, "0");
  const dd = String(today.getDate()).padStart(2, "0");
  const formattedDate = `${yyyy}-${mm}-${dd}`;

  // "Date/Month/Year" (Editable)
  const reportDateInput = document.getElementById("report_date");
  reportDateInput.value = formattedDate;

  // "Record Date" (Read-only) - showing formatted string for display
  const recordDateInput = document.getElementById("record_date");
  const options = {
    year: "numeric",
    month: "long",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  };
  // recordDateInput.value = today.toLocaleDateString("th-TH", options);

  // // 4. Case ID / Count (Simulated Auto-run)
  // const caseIdInput = document.getElementById("case_id");
  // // In a real app, this would come from a database.
  // // Generating a random ID or timestamp for demo purposes.
  // const randomId = "CASE-" + Date.now().toString().slice(-6);
  // caseIdInput.value = randomId;
});
