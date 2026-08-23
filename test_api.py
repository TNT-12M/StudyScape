import requests
s = requests.Session()

# Login
r = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
j = r.json()
print('Login:', j['success'], j['message'])
csrf = j.get('csrf_token','')

# Test 1: material_list with empty education_level
r2 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_list','education_level':'','subject':'','keyword':'','csrf_token':csrf})
j2 = r2.json()
print('Test1 (全部学段): success={}, msg={}'.format(j2['success'], j2['message']))

# Test 2: material_list with junior
r3 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_list','education_level':'junior','subject':'','keyword':'','csrf_token':csrf})
j3 = r3.json()
print('Test2 (初中): success={}, count={}'.format(j3['success'], len(j3.get('data',{}).get('materials',[]))))

# Test 3: material_update - get a material first
r4 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_list','education_level':'junior','subject':'','keyword':'','csrf_token':csrf})
j4 = r4.json()
mats = j4.get('data',{}).get('materials',[])
if mats:
    mid = mats[0]['id']
    old_level = mats[0]['education_level']
    new_level = 'senior' if old_level == 'junior' else 'junior'
    print('Test3: Updating material {} from {} to {}'.format(mid, old_level, new_level))
    r5 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_update','id':str(mid),'filename':mats[0]['filename'],'education_level':new_level,'subject':mats[0].get('subject',''),'description':mats[0].get('description',''),'csrf_token':csrf})
    j5 = r5.json()
    print('Update result: success={}, msg={}'.format(j5['success'], j5['message']))
    
    # Verify
    r6 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_list','education_level':new_level,'subject':'','keyword':'','csrf_token':csrf})
    j6 = r6.json()
    found = [m for m in j6.get('data',{}).get('materials',[]) if m['id'] == mid]
    if found:
        print('Verification: material {} has education_level={}'.format(mid, found[0]['education_level']))
    else:
        print('Verification FAIL: material {} not found in {} list'.format(mid, new_level))
    
    # Restore
    r7 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_update','id':str(mid),'filename':mats[0]['filename'],'education_level':old_level,'subject':mats[0].get('subject',''),'description':mats[0].get('description',''),'csrf_token':csrf})
    j7 = r7.json()
    print('Restored: success={}'.format(j7['success']))
