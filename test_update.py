import requests
s = requests.Session()

# Login
r = s.post('http://127.0.0.1:8080/api.php', data={'action':'login','username':'lian','password':'lian120208','csrf_token':''})
j = r.json()
csrf = j.get('csrf_token','')
print('Login csrf:', csrf[:8] + '...')

# Check current state
r1 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_list','education_level':'junior','csrf_token':csrf})
j1 = r1.json()
mats = j1.get('data',{}).get('materials',[])
target = [m for m in mats if m['id'] == 5]
if target:
    print('Before update: id=5, education_level=' + target[0]['education_level'])
    
    # Try update
    r2 = s.post('http://127.0.0.1:8080/api.php', data={
        'action':'material_update',
        'id':'5',
        'filename':target[0]['filename'],
        'education_level':'senior',
        'subject':target[0].get('subject',''),
        'description':target[0].get('description',''),
        'csrf_token':csrf
    })
    j2 = r2.json()
    print('Update result:', j2['success'], j2['message'])
    print('New csrf:', j2.get('csrf_token','')[:8] + '...')
    
    # Check if changed
    r3 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_list','education_level':'senior','csrf_token':csrf})
    j3 = r3.json()
    mats3 = j3.get('data',{}).get('materials',[])
    found = [m for m in mats3 if m['id'] == 5]
    print('After update, id=5 in senior list:', len(found) > 0)
    
    # Check junior list too
    r4 = s.post('http://127.0.0.1:8080/api.php', data={'action':'material_list','education_level':'junior','csrf_token':csrf})
    j4 = r4.json()
    mats4 = j4.get('data',{}).get('materials',[])
    found4 = [m for m in mats4 if m['id'] == 5]
    print('After update, id=5 in junior list:', len(found4) > 0)
