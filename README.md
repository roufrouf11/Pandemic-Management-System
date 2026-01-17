#  PlanBeatsNoPlanZX1 - Pandemic Management System

> **Course:** Information Systems for Biomedical Databases  
> **Institution:** University of Thessaly | MSc in Computational Medicine and Biology  
> **Author:** Konstantinos Chatziroufas

## 📖 Project Overview

**PlanBeatsNoPlanZX1** is a comprehensive Information System designed and implemented to orchestrate non-pharmaceutical interventions and manage epidemiological data during a hypothetical pandemic caused by the **"ZeroX-Virus-1"**.

Developed as part of an academic assignment, this system simulates the coordination between various entities—such as the Ministry of Health, Civil Protection, Doctors, and Epidemiologists—to limit virus transmission and manage public health resources efficiently.

## 🚀 Key Features

The system supports multiple user roles and functionalities, including:

* **Citizen & Patient Management:**
    * Registration of personal and contact details.
    * **Geolocation Tracking:** Integration of `lat` and `lng` coordinates for quarantine monitoring.
* **Diagnostic Testing Workflow:**
    * Submission of test requests (pending/approved status).
    * Recording of PCR/Rapid test results with severity grading.
    * Automated classification of results (Positive/Negative).
* **Epidemiological Surveillance:**
    * **Reports:** Detailed incident reports submitted by epidemiologists.
    * **Recommendations:** Formal health recommendations sent to Civil Protection agencies.
* **Resource & Crisis Management:**
    * Management of **Medical Imaging** files (metadata, file types) linked to patient records.
    * Automated alerts and updates to **Schools**, **Airports**, **Border Stations**, and **Media**.
    * Distribution of health materials to citizens.

## 🛠️ Tech Stack & Tools

* **Backend:** PHP
* **Database:** MySQL (Implemented via MAMP).
* **Frontend:** HTML, CSS
* **Database Design:** * [draw.io](https://app.diagrams.net/) (ER Diagrams)
    * dbdiagram.io](https://dbdiagram.io/) (Relational Schema)

## 🗄️ Database Architecture

The database follows a strict relational schema designed to ensure data integrity and normalization.

### Core Entities
The system is built upon several interconnected tables, including:
* `Citizens`, `Doctors`, `Epidemiologists`
* `Test_Results`, `Quarantine`, `Health_Materials`
* `Civil_Protection`, `Gov` (Authentication)

### Recent Updates & Expansions
The schema was recently expanded to include:
1.  **`medical_images`**: Stores metadata for patient imaging (X-rays, CTs).
2.  **`test_requests`**: Manages the approval workflow for diagnostic tests.
3.  **`epidemiologist_reports`** & **`recommendations`**: Facilitates expert communication with the government.
4.  **Geolocation Fields**: Added `latitude` and `longitude` to `Citizens` and `Quarantine` tables.

### 📊 Entity-Relationship Diagram (ERD)
*(The logical structure of the database entities and their relationships)*

<img width="714" height="802" alt="Στιγμιότυπο οθόνης (382)" src="https://github.com/user-attachments/assets/2d752406-dd0a-4b93-a558-f544d17e8da5" />


### 🗃️ Relational Schema
*(The physical implementation schema)*

<img width="924" height="656" alt="Στιγμιότυπο οθόνης (383)" src="https://github.com/user-attachments/assets/22795f4e-22b5-412d-9321-bf85d2e2c303" />

<img width="833" height="658" alt="Στιγμιότυπο οθόνης (384)" src="https://github.com/user-attachments/assets/5ecea965-cc3f-4adb-8903-89ed271fb891" />


### 🎥 Video Walkthrough - Application Demo
A comprehensive demonstration of the system's functionality:

[![Watch the video](https://img.youtube.com/vi/qze3iN-FFDE/maxresdefault.jpg)](https://youtu.be/qze3iN-FFDE)

*(Click the image above to watch the demo on YouTube)*


## ⚙️ Installation & Setup

1.  **Clone the repository:**
    ```bash
    git clone [https://github.com/roufrouf11/Bioinformatics-Project-ZX1.git](https://github.com/roufrouf11/Bioinformatics-Project-ZX1.git)
    ```
2.  **Database Import:**
    * Open your MySQL client (e.g., phpMyAdmin via MAMP).
    * Create a new database named `database_zx1`.
    * Import the `.sql` file located in the `/database` folder of this project.
    * Update your database connection settings in the PHP config file (e.g., `db_connect.php`).
4.  **Run:**
    * Start your local server (Apache/Nginx).
    * Navigate to `localhost/Bioinformatics-Project-ZX1` in your browser.

---
*© 2026 Konstantinos Chatziroufas. Created for the MSc in Computational Medicine and Biology, University of Thessaly.*
