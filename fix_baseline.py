import requests, json
s = requests.Session()
r = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
csrf = r.json().get('csrf_token','')

# Baseline should be: 2 senior (id=123,122,121,120 are actually senior - 4 in total from DB check earlier)
# Actually let's check current counts for ALL
for level in ['', 'junior', 'senior']:
    r2 = s.post('http://127.0.0.1:8080/api.php', data={
        'action':'list_questions','scope':'manage_all','page':'1','page_size':'300',
        'subject':'','education_level':level,'csrf_token':csrf
    })
    j = r2.json()
    ids = [i['id'] for i in j['data']['items']]
    print(f'level={level or "all":8s} total={j["data"]["total"]:3d}  ids={sorted(ids)[:5]}..{sorted(ids)[-3:] if len(ids)>8 else ""}  len_ids={len(ids)}')
    if level == 'senior':
        senior_ids = ids
    if level == 'junior':
        junior_ids = ids

# Correct baseline: 123,122,121,120 should be senior (4). All others junior.
print('\nChecking ids that SHOULD be senior (hardcoded DB state): id 120-123')
needs_fix = False
for sid in [120,121,122,123]:
    if sid not in senior_ids:
        print(f'  FIX NEEDED: id {sid} not in senior; moving back to senior')
        r3 = s.post('http://127.0.0.1:8080/api.php', data={
            'action':'bulk_move_questions','ids': json.dumps([sid]),
            'education_level':'senior','csrf_token':csrf
        })
        print('   ', r3.json())
        needs_fix = True
# Also check: no junior ids in senior (only 120-123 should be senior)
for sid in senior_ids:
    if sid not in [120,121,122,123]:
        print(f'  FIX NEEDED: id {sid} should be junior not senior')
        r4 = s.post('http://127.0.0.1:8080/api.php', data={
            'action':'bulk_move_questions','ids': json.dumps([sid]),
            'education_level':'junior','csrf_token':csrf
        })
        print('   ', r4.json())
        needs_fix = True

if not needs_fix:
    print('All data already at baseline (senior=120,121,122,123 = 4, rest junior = 103, total=107)')
else:
    print('\nPost-baseline verification:')
    for level in ['', 'junior', 'senior']:
        r5 = s.post('http://127.0.0.1:8080/api.php', data={
            'action':'list_questions','scope':'manage_all','page':'1','page_size':'10',
            'subject':'','education_level':level,'csrf_token':csrf
        })
        print(f'  level={level or "all":8s} total={r5.json()["data"]["total"]}')
