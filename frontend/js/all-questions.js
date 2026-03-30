// Tüm DTZ Soruları - API'siz Çalışır
const ALL_QUESTIONS = [
$(sqlite3 database/dtz_learning.db "SELECT '{ id: ' || id || ', module: \"' || module || '\", teil: ' || teil || ', level: \"' || level || '\", content: ' || content || ', correct: \"' || json_extract(correct_answer, '$.answer') || '\", explanation: \"' || explanation || '\" },' FROM question_pools;" 2>/dev/null | sed "s/'/\\'/g")
];

// Filtreleme fonksiyonu
function getQuestions(module, teil) {
    return ALL_QUESTIONS.filter(q => q.module === module && q.teil === parseInt(teil));
}

// Export
if (typeof module !== 'undefined') module.exports = { ALL_QUESTIONS, getQuestions };
