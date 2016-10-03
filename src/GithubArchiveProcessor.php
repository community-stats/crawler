<?php
/**
 *
 *
 *
 *
 */

namespace CommunityStats\Crawler;

class GithubArchiveProcessor
{
    /** @var  \PDO */
    private $pdo;
    
    protected function pdoQueryFetchAll(string $query)
    {
        $statement =  $this->pdo->query($query);
        if (!$statement) {
            return [];
        }
        return $statement->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    private function initialize()
    {
        $isInitialized = count($this->pdoQueryFetchAll('SELECT name FROM sqlite_master where type = "table"')) > 0;
        if (!$isInitialized) {
            $this->pdo->query("Create Table flags (
key TEXT,
value TEXT
)")->execute();
            $this->pdo->query("Create Table githubEvents (
id INTEGER PRIMARY KEY,
type TEXT,
actor_id INTEGER,
repo_id INTEGER,
repo_name TEXT,
created_at TEXT,
bare TEXT
)")->execute();
        }
    }
    
    private function getArchiveForDay(\DateTime $date)
    {
        $sourceUrlPattern = 'http://data.githubarchive.org/%s.json.gz';
        
        $dateKey = $date->format('Y-m-d-G');
        $url = sprintf($sourceUrlPattern, $dateKey);
        $targetPath = StaticConfig::CACHE_DIR . "githubarchive/";
        if (!file_exists($targetPath)) {
            mkdir($targetPath, 0777, true);
        }
        $cacheFile = $targetPath . $dateKey.'.json.gz';
        if (file_exists($cacheFile)) {
            $fileCompressed = file_get_contents($cacheFile);
        } else {
            $fileCompressed = file_get_contents($url);
            file_put_contents($cacheFile, $fileCompressed);
        }
        $file = gzdecode($fileCompressed);
        $data = explode("\n", $file);
        //$data = preg_split("/\n/", $file);
        $repoPattern = '#,"repo":{"id":2884111,#';
        $dataRows = array_filter($data, function ($v) use ($repoPattern) {
            //return strpos($v, ',"repo":{"id":2884111,') > -1;
            //return preg_match($repoPattern, $v);
            return json_decode($v, true)['repo']['id'] === 2884111;
        });
        //var_dump(json_decode(current($data), true));
        var_dump('filtered number of results', $date, count($dataRows));
        return $dataRows;
    }
    
    public function run()
    {
        if (getenv("STORAGE_TARGET") === "mysql") {
            $dbString = "mysql:host=flyingmana-mysql;dbname=james-sandbox";
            $this->pdo = new \PDO($dbString, getenv("DB_USER"), getenv("DB_PASS"));

        } else {
            $dbString = "sqlite:".StaticConfig::CACHE_DIR."/githubArchive.sqlite3";
            $this->pdo = new \PDO($dbString);
            $this->initialize();
        }
        gc_disable();
        foreach (range(0,25) as $run) {
            $date = $this->getDate();
            var_dump('run', $run, $date);
            $rows = $this->getArchiveForDay($date);
            $this->persistRows($rows);
            $this->updateDate($date);
            gc_collect_cycles();
        }        
    }
    
    private function getDate(): \DateTime
    {
        $dateRow = $this->pdoQueryFetchAll('Select value from flags where key = \'github_archive_date\'');
        if (!isset($dateRow[0]['value'])) {
            $date = new \DateTime('Yesterday 12:00');
            $statement = $this->pdo->prepare("INSERT INTO flags (key,value) VALUES ('github_archive_date', :value)");
            $statement->execute(['value' => $date->getTimestamp()]);
        } else {
            $date = new \DateTime();
            $date->setTimestamp($dateRow[0]['value']);
        }
        return $date;
    }
    
    private function updateDate(\DateTime $date): \DateTime
    {
        $date->sub(new \DateInterval('PT1H'));
        $statement = $this->pdo->prepare("UPDATE flags SET value = :value where key = 'github_archive_date'");
        $statement->execute(['value' => $date->getTimestamp()]);
        return $date;
    }
    
    private function persistRows($rows)
    {
        $statement = $this->pdo->prepare("INSERT OR IGNORE INTO githubEvents 
(id, type, actor_id, repo_id, repo_name, created_at, bare)
VALUES (:id, :type, :actor_id, :repo_id, :repo_name, :created_at, :bare)");
        foreach ($rows as $row) {
            $rowData = json_decode($row, true);            
            $statement->execute([
                
                'id' => $rowData['id'],
                'type' => $rowData['type'],
                'actor_id' => $rowData['actor']['id'],
                'repo_id' => $rowData['repo']['id'],
                'repo_name' => $rowData['repo']['name'],
                'created_at' => $rowData['created_at'],
                
                'bare' => $row,
            ]);
        }
    }

}
