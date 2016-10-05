
SELECT 
    CONCAT(YEAR(created_at), '-', WEEK(created_at)) as week, `type`, COUNT(*)
FROM
    githubEvents
    where 
    `type` in ('ForkEvent', 'IssuesEvent', 'PullRequestEvent', 'WatchEvent')
GROUP BY CONCAT(YEAR(created_at), '/', WEEK(created_at)), `type`
order by created_at;
