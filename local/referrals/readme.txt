Notes:
------
1. This plugin requires a database table called mdl_local_referrals (where mdl_ will be replaced by your moodle database table prefix), this is not automatically handled yet. On MySQL, this would be something like
CREATE TABLE mdl_local_referrals (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    userid BIGINT NOT NULL,
    courseid BIGINT NOT NULL,
    coursecode VARCHAR(20) NOT NULL,
    referralid VARCHAR(64) NOT NULL,
    studentid VARCHAR(64),
    timecreated BIGINT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

2. Please report issues at https://github.com/hn-88/moodle-plugin-local-referrals/issues

3. This was created with the help of ChatGPT and Gemini - the relevant conversations will be made available on the wiki or issues if possible.
