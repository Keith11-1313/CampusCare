"""
CampusCare - Bulk Seed Data Generator
Generates bulk_seed_data.sql to be imported AFTER campuscare.sql + seed_data.sql
"""

import random, os, textwrap
from datetime import date, timedelta

random.seed(42)

# -- Config --
EMAIL_DOMAIN  = "student.edu.com"
SECTIONS      = ["A", "B"]
STU_PER_SEC   = (30, 40)
PASSWORD_HASH = "$2y$10$BdI37HGzeGflcH1PxxaTv.RYEK7Eth/YJobXLY1TE3WR5JXzFQTK."  # Rep@1234
CHUNK         = 200

# Existing users in seed_data.sql: admin=1, nurse_garcia=2, nurse_santos=3
EXISTING_USER_COUNT = 3
# No existing students in seed_data.sql
EXISTING_STUDENT_COUNT = 0

YEARS = [
    {"id": 1, "prefix": "2026", "birth": (2007, 2008)},
    {"id": 2, "prefix": "2025", "birth": (2006, 2007)},
    {"id": 3, "prefix": "2024", "birth": (2005, 2006)},
    {"id": 4, "prefix": "2023", "birth": (2004, 2005)},
]

PROGRAM_CODES = [
    "BSA","BSAIS","BSBAFM","BSBAHRM","BSBAMM","BSENTREP","BSHM","BSOA","BSTM",
    "BSCRIM","BSISM",
    "BSEENG","BSEEC","BSESCI","BSETLE","BECE",
    "BSCPE","BSEE","BSECE","BSIE",
    "ABPOLSCI","BACOMM","BPA","BSCS","BSEMC","BSIS","BSIT","BSMATH","BSPSYCH","BSSW",
]

# -- Name pools (Filipino) --
MALE_FIRST = [
    "Juan","Pedro","Luis","Mark","Rafael","Kenneth","Jerome","Bryan","Daniel",
    "Francis","Jose","Carlos","Antonio","Roberto","Eduardo","Fernando","Michael",
    "John","Angelo","Christian","Kevin","Paolo","Joshua","Ian","Ryan","Vincent",
    "Gabriel","Justine","Adrian","Paul","Aldrin","Jayson","Rodel","Ronnie",
    "Marvin","Ariel","Benedict","Elmer","Gerald","Tristan","Nathaniel","Jericho",
    "Alvin","Dominic","Patrick","Rico","Emilio","Marco","Enrique","Andrei",
    "Cedric","Darren","Erwin","Felix","Gino","Harold","Ivan","Jomar","Kurt",
    "Leo","Manuel","Nelson","Oliver","Philip","Quentin","Renzo","Steven",
    "Timothy","Ulysses","Warren","Xavier","Yvan","Zeke","Noel","Ricky",
    "Jobert","Crispin","Renato","Edgardo","Voltaire","Dino","Ernesto","Lester",
    "Rommel","Danilo","Nestor","Virgilio","Wilfredo","Rolando","Armando","Dennis",
    "Rogelio","Carlito","Dante","Efren","Gilbert","Henry","Isagani","Joel",
]
FEMALE_FIRST = [
    "Maria","Angela","Sofia","Camille","Isabelle","Patricia","Christine","Nicole",
    "Rachel","Samantha","Elena","Carmen","Diana","Linda","Sandra","Gloria",
    "Teresa","Anna","Mary","Joyce","Hazel","Erica","Grace","Michelle","Jessica",
    "Katrina","Bianca","Rhea","Joan","Denise","Cherry","Precious","Alyssa",
    "Jasmine","Clarisse","Angelica","Rowena","Charisse","Maribel","Rosalie",
    "Mariel","Daisy","Ivy","Lea","Jessa","Kaye","Abigail","Althea","Janine",
    "Bea","Czarina","Divina","Eunice","Faith","Gladys","Hannah","Irene",
    "Joanna","Kristine","Liza","Mylene","Nina","Odette","Pia","Queen",
    "Richelle","Sheila","Trixie","Ursula","Vanessa","Wilma","Yvette","Zenaida",
    "April","Bernadette","Cynthia","Dolores","Erlinda","Felicitas","Gemma",
    "Helen","Imelda","Josefina","Lorna","Marilyn","Nora","Olivia","Rosemarie",
    "Shirley","Victoria","Agnes","Beverly","Carla","Donna","Edith","Flora",
]
LAST_NAMES = [
    "Dela Cruz","Santos","Reyes","Garcia","Lopez","Rivera","Mendoza","Torres",
    "Bautista","Villanueva","Aquino","Pascual","Ramos","Flores","Gonzales",
    "Cruz","Soriano","Aguilar","Navarro","Castro","Perez","Marquez","Gomez",
    "Domingo","Ocampo","Dizon","David","Sarmiento","Tolentino","Salazar",
    "Manalo","Del Rosario","Hernandez","Santiago","De Leon","Mercado","Magno",
    "Francisco","Miranda","Enriquez","Corpuz","Valdez","Padilla","Ignacio",
    "Lim","Tan","Chua","Yu","Ong","Co","Libang","Pineda","Serrano","Velasco",
    "Cabrera","Estrada","Galang","Javier","Lacson","Manalang","Natividad",
    "Pangilinan","Quiambao","Rosales","Suarez","Trinidad","Uy","Villegas",
    "Zamora","Almario","Bueno","Concepcion","De Guzman","Espiritu","Fajardo",
    "Guevara","Hidalgo","Ilagan","Javillonar","Kanapi","Lagman","Macatangay",
    "Napoles","Ordonez","Palma","Quintos","Recio","Salvacion","Tinio",
    "Umali","Viray","Yap","Zarate","Bernardino","Dela Pena","Evangelista",
]
STREETS = [
    "10th Ave","11th Ave","Rizal Ave Ext","A. Mabini St","Sangandaan St",
    "Gen. Luis St","EDSA","Susano Rd","Libis Talisay","Zapote St",
    "Caimito St","Bayabas St","Samson Rd","Gen. San Miguel St","Biglang Awa St",
    "F. Huertas St","Mayon St","Kanlaon St","Reparo St","Capulong St",
    "9th Ave","P. Zamora St","M.H. Del Pilar St","Dagat-Dagatan Ave",
    "Victory Ave","Monumento Circle","Libis Espina","Palmera St",
    "Bagong Barrio Ave","Deparo Rd","Camarin Rd","Llano Rd",
]
CITIES = [
    "Caloocan","North Caloocan","Bagong Silang, Caloocan",
    "Bagong Barrio, Caloocan","Camarin, Caloocan","Deparo, Caloocan",
    "Sangandaan, Caloocan","Grace Park, Caloocan","Monumento, Caloocan",
    "Dagat-Dagatan, Caloocan","Maypajo, Caloocan","Baesa, Caloocan",
]
BLOOD_TYPES = ["A+","A-","B+","B-","AB+","AB-","O+","O-"]

# -- Medical data pools --
ALLERGENS_DATA = [
    ("Penicillin",  "Rash and hives",               "Severe"),
    ("Peanuts",     "Swelling, difficulty breathing","Severe"),
    ("Dust mites",  "Sneezing, itchy eyes",         "Mild"),
    ("Sulfa drugs", "Skin rash",                    "Moderate"),
    ("Latex",       "Contact dermatitis",            "Mild"),
    ("Shellfish",   "Hives, stomach cramps",        "Moderate"),
    ("Aspirin",     "Stomach upset, nausea",         "Mild"),
    ("Pollen",      "Sneezing, runny nose",          "Mild"),
    ("Dairy",       "Bloating, diarrhea",            "Moderate"),
    ("Soy",         "Hives, itching",                "Mild"),
    ("Eggs",        "Skin rash, nausea",             "Moderate"),
    ("Ibuprofen",   "Stomach pain",                  "Mild"),
]
CHRONIC_DATA = [
    ("Asthma",                  "Active",   "Uses inhaler as needed"),
    ("Hypertension (Stage 1)",  "Active",  "On daily medication, needs BP monitoring"),
    ("Type 1 Diabetes",         "Active",  "Insulin-dependent, carries glucose monitor"),
    ("Migraine",                "Active",  "Triggered by stress and bright lights"),
    ("Scoliosis",               "Resolved", "Mild curvature, annual monitoring"),
    ("Epilepsy",                "Resolved", "Controlled with medication"),
    ("Allergic Rhinitis",       "Active",  "Seasonal flare-ups"),
    ("GERD",                    "Resolved", "Diet-controlled"),
    ("Anemia",                  "Active",  "Iron supplements prescribed"),
]
MEDICATIONS_MAP = {
    "Asthma":                 ("Salbutamol Inhaler","100mcg/puff","As needed","Dr. Reyes"),
    "Hypertension (Stage 1)": ("Losartan","50mg","Once daily","Dr. Lopez"),
    "Type 1 Diabetes":        ("Insulin Glargine","20 units","Once daily at bedtime","Dr. Santos"),
    "Migraine":               ("Sumatriptan","50mg","As needed at migraine onset","Dr. Garcia"),
    "Epilepsy":               ("Levetiracetam","500mg","Twice daily","Dr. Mendoza"),
    "Allergic Rhinitis":      ("Cetirizine","10mg","Once daily","Dr. Cruz"),
    "GERD":                   ("Omeprazole","20mg","Once daily before meals","Dr. Torres"),
    "Anemia":                 ("Ferrous Sulfate","325mg","Once daily","Dr. Ramos"),
}
VACCINES = [
    "Hepatitis B","COVID-19 (Pfizer)","COVID-19 (Moderna)","Influenza",
    "Tetanus Toxoid","HPV","MMR",
]
# (category, description, assessment, treatment)
VISIT_COMPLAINTS = [
    ("Headache - Minor",    "Dizziness and mild tension",              "Mild tension headache",                 "Paracetamol 500mg given. Advised rest."),
    ("Headache - Severe",   "Throbbing pain, sensitivity to light",    "Possible migraine episode",             "Paracetamol 500mg, dark room rest."),
    ("Headache - Migraine", "Recurring migraine with aura",            "Migraine with visual aura",             "Sumatriptan and rest in dark room."),
    ("Dizziness",           "Lightheaded after standing up quickly",   "Orthostatic hypotension",               "ORS given. Monitored for 30 mins."),
    ("Fainting",            "Fainted during morning flag ceremony",    "Vasovagal syncope",                     "Laid supine, legs elevated. Monitoring."),
    ("Sore Throat",         "Sore throat and runny nose",              "Upper respiratory tract infection",     "Vitamin C and lozenges provided."),
    ("Cough",               "Persistent dry cough for 2 days",         "Acute bronchitis",                      "Decongestant and Vitamin C provided."),
    ("Cold / Flu",          "Runny nose, sneezing, body aches",        "Common cold",                           "Decongestant and Vitamin C provided."),
    ("Difficulty Breathing","Shortness of breath during PE",           "Exercise-induced bronchoconstriction",  "Inhaler administered. Rested."),
    ("Asthma Attack",       "Wheezing and chest tightness",            "Acute asthma exacerbation",             "Salbutamol inhaler. Monitored."),
    ("Stomach Pain",        "Stomach ache and nausea after lunch",     "Acute gastritis",                       "Antacid given. Advised regular meals."),
    ("Nausea / Vomiting",   "Nausea and vomiting since morning",       "Possible food poisoning",               "ORS given. Advised bland diet."),
    ("Diarrhea",            "Loose stools since last night",           "Acute gastroenteritis",                 "ORS and Loperamide given."),
    ("Loss of Appetite",    "No appetite for 3 days",                  "Possible stress-related anorexia",      "Counseling suggested. Vitamin B complex."),
    ("Fever",               "Fever, body aches, and fatigue",          "Possible viral infection",              "Paracetamol 500mg given. Advised rest at home."),
    ("Fatigue / Weakness",  "Feeling faint and weak all day",          "Possible dehydration",                  "ORS given. Monitored for 30 mins."),
    ("Menstrual Cramps",    "Severe menstrual cramps",                 "Dysmenorrhea",                          "Mefenamic acid 250mg given. Hot compress."),
    ("Eye Problem",         "Eye irritation and redness",              "Allergic conjunctivitis",               "Artificial tears administered."),
    ("Ear Pain",            "Sharp pain in left ear",                  "Possible otitis media",                 "Paracetamol given. ENT referral issued."),
    ("Toothache",           "Severe toothache on lower right",         "Dental issue, referred to dentist",     "Mefenamic acid for pain. Dental referral issued."),
    ("Anxiety / Panic Attack","Anxiety and chest tightness",           "Anxiety-related symptoms",              "Breathing exercises guided. Counseling suggested."),
    ("Wound / Cut",         "Minor cut on hand from paper",            "Superficial laceration",                "Wound cleaned. Bandage applied."),
    ("Skin Rash",           "Rash on forearm, itchy",                  "Allergic dermatitis",                   "Antihistamine given. Area cleaned."),
    ("Allergic Reaction",   "Hives after eating seafood",              "Urticaria, allergic reaction",          "Cetirizine 10mg given. Monitoring."),
    ("Insect Bite",         "Swollen insect bite on arm",              "Insect bite with localized swelling",   "Antihistamine and cold compress."),
    ("Body Pain",           "General body aches after PE",             "Muscle soreness",                       "Topical analgesic applied."),
    ("Back Pain",           "Back pain during prolonged sitting",      "Mild lower back strain",                "Hot compress applied. Stretching exercises."),
    ("Chest Pain",          "Mild chest discomfort",                   "Non-cardiac chest pain",                "ECG normal. Advised follow-up."),
    ("Joint Pain",          "Knee pain after basketball",              "Mild patellar strain",                  "Ice pack applied. Knee support bandage."),
    ("Muscle Cramp",        "Leg cramp during PE class",               "Exercise-induced muscle cramp",         "Stretching and hydration."),
    ("Sprain / Strain",     "Sprained ankle from PE class",            "Grade 1 ankle sprain",                  "RICE method applied. Elastic bandage wrap."),
    ("Fracture (Suspected)","Fell and hurt wrist, swelling noted",     "Suspected distal radius fracture",      "Splint applied. X-ray referral."),
    ("Bruise / Contusion",  "Bruise on shin from bumping desk",        "Minor contusion",                       "Cold compress applied."),
    ("Burns",               "Minor burn on finger from lab",           "First-degree burn",                     "Cool water applied. Burn ointment."),
]
VISIT_STATUSES = ["Completed","Completed","Completed","Completed","Follow-up","Referred"]

# -- Date ranges for visits (school year) --
SY_START = date(2025, 8, 4)
SY_CURRENT = date(2026, 4, 13)
SY_TOTAL_DAYS = (SY_CURRENT - SY_START).days
CURRENT_WEEK_START = date(2026, 4, 3)
CURRENT_WEEK_END = date(2026, 4, 13)

def make_visit_row(sid, vdate):
    """Generate a single clinic visit SQL value tuple."""
    sys_bp = random.randint(100, 135)
    dia_bp = random.randint(60, 88)
    temp = round(random.uniform(36.2, 38.0), 1)
    hr = random.randint(60, 100)
    rr = random.randint(14, 22)
    wt = round(random.uniform(45.0, 85.0), 1)
    ht = round(random.uniform(150.0, 185.0), 1)
    nurse = random.choice([2, 3])
    comp = random.choice(VISIT_COMPLAINTS)
    status = random.choice(VISIT_STATUSES)
    fu_date = "NULL"
    fu_notes = "NULL"
    if status == "Follow-up":
        fu = vdate + timedelta(days=random.randint(7, 30))
        fu_date = "'%s'" % fu.strftime("%Y-%m-%d")
        fu_notes = "'Follow up for re-assessment'"
    elif status == "Referred":
        fu_notes = "'Referred to specialist'"
    hour = random.randint(7, 16)
    minute = random.choice(["00","15","30","45"])
    return (
        "(%d, %d, '%s %02d:%s:00', '%d/%d', %.1f, %d, %d, %.1f, %.1f, '%s', '%s', '%s', '%s', %s, %s, '%s')" % (
            sid, nurse, vdate.strftime("%Y-%m-%d"), hour, minute,
            sys_bp, dia_bp, temp, hr, rr, wt, ht,
            esc(comp[0]), esc(comp[1]), esc(comp[2]), esc(comp[3]),
            fu_notes, fu_date, status
        )
    )

# -- Helpers --
def slug(s):
    return s.lower().replace(" ","_").replace(".","").replace("'","")

def phone():
    return "09%02d%07d" % (random.randint(10,99), random.randint(1000000,9999999))

def esc(s):
    if s is None:
        return s
    return s.replace("'", "\\'")

def write_inserts(f, table, cols, rows):
    if not rows:
        return
    col_str = ", ".join("`%s`" % c for c in cols)
    for i in range(0, len(rows), CHUNK):
        chunk = rows[i:i+CHUNK]
        f.write("INSERT INTO `%s` (%s) VALUES\n" % (table, col_str))
        f.write(",\n".join(chunk))
        f.write(";\n")

# -- Main --
out_path = os.path.join(os.path.dirname(os.path.abspath(__file__)), "bulk_seed_data.sql")

next_user_id    = EXISTING_USER_COUNT + 1      # 5
next_student_id = EXISTING_STUDENT_COUNT + 1   # 1
seq = {}  # pyre-ignore
for y in YEARS:
    seq[y["prefix"]] = 1

rep_rows      = []
student_rows  = []
allergy_rows  = []
condition_rows= []
med_rows      = []
immun_rows    = []
contact_rows  = []
visit_rows    = []
students_list = []

print("Generating classes ...")

for prog_idx, prog_code in enumerate(PROGRAM_CODES):
    prog_id = prog_idx + 1  # DB IDs are 1-based
    for year in YEARS:
        num_sections = random.choice([1, 2])
        for si in range(num_sections):
            section = SECTIONS[si]

            # -- Rep user --
            g = random.choice(["M","F"])
            if g == "M":
                fn = random.choice(MALE_FIRST)
            else:
                fn = random.choice(FEMALE_FIRST)
            ln = random.choice(LAST_NAMES)
            uname = "rep_%s_%s_%d%s" % (slug(ln), prog_code, year["id"], section)
            uname = uname.replace("__","_")
            email = "%s.%s.rep@%s" % (slug(fn), slug(ln), EMAIL_DOMAIN)

            val = "('%s', '%s', '%s', '%s', '%s', 'rep', %d, %d, '%s', 'active')" % (
                esc(uname), PASSWORD_HASH, esc(fn), esc(ln), esc(email),
                prog_id, year["id"], section
            )
            rep_rows.append(val)
            rep_user_id = next_user_id
            next_user_id += 1  # pyre-ignore

            # -- Students --
            n = random.randint(STU_PER_SEC[0], STU_PER_SEC[1])
            for _ in range(n):
                sg = random.choice(["Male","Female"])
                if sg == "Male":
                    sfn = random.choice(MALE_FIRST)
                else:
                    sfn = random.choice(FEMALE_FIRST)
                sln = random.choice(LAST_NAMES)
                if random.random() > 0.2:
                    smn = random.choice(LAST_NAMES)
                else:
                    smn = None

                by = random.randint(year["birth"][0], year["birth"][1])
                bm = random.randint(1, 12)
                bd = random.randint(1, 28)
                dob = "%d-%02d-%02d" % (by, bm, bd)

                prefix = year["prefix"]
                sid_str = "%s-%06d-N" % (prefix, seq[prefix])
                seq[prefix] += 1  # pyre-ignore

                s_phone = phone()
                s_email = "%s.%s%d@%s" % (slug(sfn), slug(sln), random.randint(1,999), EMAIL_DOMAIN)
                addr = "%d %s, %s" % (random.randint(1,999), random.choice(STREETS), random.choice(CITIES))
                bt = random.choice(BLOOD_TYPES)

                if smn is not None:
                    mn_sql = "'%s'" % esc(smn)
                else:
                    mn_sql = "NULL"

                val = "('%s', '%s', '%s', %s, '%s', '%s', %d, %d, '%s', '%s', '%s', '%s', '%s', 'active', %d)" % (
                    sid_str, esc(sfn), esc(sln), mn_sql, dob,
                    sg, prog_id, year["id"], section, s_phone,
                    esc(s_email), esc(addr), bt, rep_user_id
                )
                student_rows.append(val)

                db_id = next_student_id
                next_student_id += 1  # pyre-ignore
                students_list.append({
                    "id": db_id,
                    "fn": sfn,
                    "ln": sln,
                    "gender": sg,
                })

print("  -> %d class reps" % len(rep_rows))
print("  -> %d students" % len(students_list))

# -- Medical records --
print("Generating medical records ...")

for s in students_list:
    sid = s["id"]
    s_ln = s["ln"]

    # Emergency contact (every student)
    rel = random.choice(["Father","Mother","Guardian"])
    if rel == "Father":
        cfn = random.choice(MALE_FIRST)
    else:
        cfn = random.choice(FEMALE_FIRST)
    c_phone = phone()
    c_email = "%s.%s%d@email.com" % (slug(cfn), slug(s_ln), random.randint(1,99))
    contact_name = "%s %s" % (esc(cfn), esc(s_ln))
    contact_rows.append(
        "(%d, '%s', '%s', '%s', '%s', 1)" % (sid, contact_name, rel, c_phone, esc(c_email))
    )

    # Allergies (~10%)
    if random.random() < 0.10:
        a = random.choice(ALLERGENS_DATA)
        allergy_rows.append("(%d, '%s', '%s', '%s', NULL)" % (sid, esc(a[0]), esc(a[1]), a[2]))

    # Chronic conditions (~5%)
    if random.random() < 0.05:
        c = random.choice(CHRONIC_DATA)
        dy = random.randint(2015, 2024)
        dm = random.randint(1, 12)
        dd = random.randint(1, 28)
        ddate = "%d-%02d-%02d" % (dy, dm, dd)
        condition_rows.append(
            "(%d, '%s', '%s', '%s', '%s')" % (sid, esc(c[0]), ddate, c[1], esc(c[2]))
        )
        if c[0] in MEDICATIONS_MAP:
            m = MEDICATIONS_MAP[c[0]]
            med_rows.append(
                "(%d, '%s', '%s', '%s', '%s', '%s', NULL, NULL)" % (
                    sid, esc(m[0]), esc(m[1]), esc(m[2]), esc(m[3]), ddate
                )
            )

    # Immunizations (~80%)
    if random.random() < 0.80:
        for _ in range(random.randint(1, 3)):
            vax = random.choice(VACCINES)
            vy = random.randint(2010, 2025)
            vm = random.randint(1, 12)
            vd = random.randint(1, 28)
            immun_rows.append(
                "(%d, '%s', '%d-%02d-%02d', 'Complete', 'Health Center', NULL, NULL)" % (
                    sid, esc(vax), vy, vm, vd
                )
            )

    # Clinic visits (~15%) - spread across the school year
    if random.random() < 0.15:
        for _ in range(random.randint(1, 3)):
            vdate = SY_START + timedelta(days=random.randint(0, SY_TOTAL_DAYS))
            visit_rows.append(make_visit_row(sid, vdate))

print("  -> %d allergies" % len(allergy_rows))
print("  -> %d chronic conditions" % len(condition_rows))
print("  -> %d medications" % len(med_rows))
print("  -> %d immunizations" % len(immun_rows))
print("  -> %d emergency contacts" % len(contact_rows))
print("  -> %d visits (school year)" % len(visit_rows))

# -- Current-week visit boost (ensures dashboard has fresh data) --
print("Generating current-week visits ...")
cw_count = min(80, len(students_list))
cw_students = random.sample(students_list, cw_count)
for s in cw_students:
    sid = s["id"]
    vdate = CURRENT_WEEK_START + timedelta(days=random.randint(0, (CURRENT_WEEK_END - CURRENT_WEEK_START).days))
    visit_rows.append(make_visit_row(sid, vdate))
print("  -> %d current-week visits added" % cw_count)
print("  -> %d total visits" % len(visit_rows))

# -- Write SQL --
print("\nWriting %s ..." % out_path)

with open(out_path, "w", encoding="utf-8") as f:
    f.write("-- ============================================================\n")
    f.write("-- CampusCare: Bulk Seed Data\n")
    f.write("-- Run AFTER campuscare.sql + seed_data.sql\n")
    f.write("-- ============================================================\n\n")
    f.write("USE `campuscare`;\n\n")

    f.write("-- Class Representatives (password: Rep@1234)\n")
    write_inserts(f, "users",
        ["username","password","first_name","last_name","email",
         "role","assigned_program_id","assigned_year_level_id","assigned_section","status"],
        rep_rows)
    f.write("\n")

    f.write("-- Students\n")
    write_inserts(f, "students",
        ["student_id","first_name","last_name","middle_name","date_of_birth",
         "gender","program_id","year_level_id","section","contact_number",
         "email","address","blood_type","status","created_by"],
        student_rows)
    f.write("\n")

    f.write("-- Allergies\n")
    write_inserts(f, "allergies",
        ["student_id","allergen","reaction","severity","notes"], allergy_rows)

    f.write("\n-- Chronic Conditions\n")
    write_inserts(f, "chronic_conditions",
        ["student_id","condition_name","diagnosis_date","status","notes"], condition_rows)

    f.write("\n-- Medications\n")
    write_inserts(f, "medications",
        ["student_id","medication_name","dosage","frequency","prescribing_doctor",
         "start_date","end_date","notes"], med_rows)

    f.write("\n-- Immunizations\n")
    write_inserts(f, "immunizations",
        ["student_id","vaccine_name","date_administered","dose_number",
         "administered_by","next_dose_date","notes"], immun_rows)

    f.write("\n-- Emergency Contacts\n")
    write_inserts(f, "emergency_contacts",
        ["student_id","contact_name","relationship","phone_number","email","is_primary"],
        contact_rows)

    f.write("\n-- Clinic Visits\n")
    write_inserts(f, "visits",
        ["student_id","attended_by","visit_date","blood_pressure","temperature",
         "pulse_rate","respiratory_rate","weight","height","complaint_category",
         "complaint","assessment","treatment","follow_up_notes","follow_up_date",
         "status"], visit_rows)

    f.write("\n-- Bulk seed data complete.\n")

print("Done!")
