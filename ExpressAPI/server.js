// Q5

const express = require("express");

const app = express();
const PORT = 3000;

// Express REST API
app.get("/api/students", (req, res) => {
    const students = [
        {
            id: 1,
            name: "Amit Patel",
            course: "BCA"
        },
        {
            id: 2,
            name: "Neha Shah",
            course: "MCA"
        },
        {
            id: 3,
            name: "Rahul Desai",
            course: "BCA"
        }
    ];

    res.json(students);
});

app.listen(PORT, () => {
    console.log(`Express API running at http://localhost:${PORT}`);
});



//Q6

// const express = require("express");

// const app = express();
// const PORT = 3000;


// app.get("/api/php-students", async (req, res) => {
//     try {
//         const response = await fetch(
//             "http://localhost/39_Aparnapatel_A3/Shopping_Cart/php_api.php"
//         );

//         const data = await response.json();

//         if (!response.ok) {
//             return res.status(response.status).json({
//                 message: "PHP API request failed",
//                 details: data
//             });
//         }

//         res.json({
//             message: "Data received from PHP REST API",
//             students: data
//         });

//     } catch (error) {
//         res.status(500).json({
//             message: "Unable to connect to PHP API",
//             details: error.message
//         });
//     }
// });


// app.listen(PORT, () => {
//     console.log(`Express server running at http://localhost:${PORT}`);
// });
